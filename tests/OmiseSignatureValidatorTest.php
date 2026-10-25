<?php

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Route;
use Soap\OmiseWebhooks\Exceptions\InvalidOmiseSignature;
use Soap\OmiseWebhooks\OmiseSignatureValidator;
use Soap\OmiseWebhooks\OmiseWebhooks;
use Soap\OmiseWebhooks\Tests\DummyJob;
use Spatie\WebhookClient\Models\WebhookCall;

beforeEach(function () {
    Event::fake();

    Route::omiseWebhooks('omise/webhooks');
    Route::omiseWebhooks('omise/webhooks/{configKey}');

    config([
        'omise-webhooks.jobs' => ['my_key' => DummyJob::class],
        'omise-webhooks.verify_signature' => true,
        'omise-webhooks.signing_secret' => base64_encode('current_secret'),
    ]);

    $this->payload = ['id' => 'evnt_test_1', 'key' => 'my.key', 'name' => 'value'];
});

afterEach(function () {
    OmiseWebhooks::flushState();
});

function omiseHeaders(array $payload, string|array $secrets, ?int $timestamp = null): array
{
    $timestamp ??= time();
    $signatures = array_map(
        fn (string $secret) => hash_hmac('sha256', $timestamp.'.'.json_encode($payload), $secret),
        (array) $secrets,
    );

    return [
        'Omise-Signature' => implode(',', $signatures),
        'Omise-Signature-Timestamp' => (string) $timestamp,
    ];
}

it('accepts a request with a valid signature', function () {
    $this->postJson('omise/webhooks', $this->payload, omiseHeaders($this->payload, 'current_secret'))
        ->assertSuccessful();

    expect(WebhookCall::count())->toBe(1);
});

it('accepts a request signed with either secret during rotation', function () {
    $headers = omiseHeaders($this->payload, ['old_secret', 'current_secret']);

    $this->postJson('omise/webhooks', $this->payload, $headers)->assertSuccessful();

    expect(WebhookCall::count())->toBe(1);
});

it('uses the signing secret of the given config key', function () {
    config(['omise-webhooks.signing_secret_other' => base64_encode('other_secret')]);

    $this->postJson('omise/webhooks/other', $this->payload, omiseHeaders($this->payload, 'other_secret'))
        ->assertSuccessful();

    expect(WebhookCall::count())->toBe(1);
});

it('rejects a request with an invalid signature', function () {
    $this->postJson('omise/webhooks', $this->payload, omiseHeaders($this->payload, 'wrong_secret'))
        ->assertStatus(403);

    expect(WebhookCall::count())->toBe(0);
});

it('rejects a request without signature headers', function () {
    $this->postJson('omise/webhooks', $this->payload)->assertStatus(403);

    expect(WebhookCall::count())->toBe(0);
});

it('rejects a request whose payload was tampered with', function () {
    $headers = omiseHeaders($this->payload, 'current_secret');

    $this->postJson('omise/webhooks', [...$this->payload, 'name' => 'changed'], $headers)
        ->assertStatus(403);

    expect(WebhookCall::count())->toBe(0);
});

it('rejects a request with an expired timestamp', function () {
    $headers = omiseHeaders($this->payload, 'current_secret', time() - 301);

    $this->postJson('omise/webhooks', $this->payload, $headers)->assertStatus(403);

    expect(WebhookCall::count())->toBe(0);
});

it('skips the timestamp check when the tolerance is zero', function () {
    config(['omise-webhooks.signature_tolerance' => 0]);
    $headers = omiseHeaders($this->payload, 'current_secret', time() - 3600);

    $this->postJson('omise/webhooks', $this->payload, $headers)->assertSuccessful();

    expect(WebhookCall::count())->toBe(1);
});

it('does not verify the signature when verification is disabled', function () {
    config(['omise-webhooks.verify_signature' => false]);

    $this->postJson('omise/webhooks', $this->payload)->assertSuccessful();

    expect(WebhookCall::count())->toBe(1);
});

it('verifies the signature by default', function () {
    $config = require __DIR__.'/../config/omise-webhooks.php';

    expect($config['verify_signature'])->toBeTrue();
});

it('rejects a request when no signing secret is configured', function () {
    config(['omise-webhooks.signing_secret' => null]);

    $this->postJson('omise/webhooks', $this->payload, omiseHeaders($this->payload, ''))
        ->assertStatus(403);

    expect(WebhookCall::count())->toBe(0);
});

it('rejects a request for an unknown config key', function () {
    $this->postJson('omise/webhooks/unknown', $this->payload, omiseHeaders($this->payload, 'current_secret'))
        ->assertStatus(403);

    expect(WebhookCall::count())->toBe(0);
});

it('uses the signing secret resolver instead of the config', function () {
    $resolvedFor = [];

    OmiseWebhooks::resolveSigningSecretUsing(function (Request $request, ?string $configKey) use (&$resolvedFor) {
        $resolvedFor[] = $configKey;

        return base64_encode("tenant_{$configKey}_secret");
    });

    $this->postJson('omise/webhooks/acme', $this->payload, omiseHeaders($this->payload, 'tenant_acme_secret'))
        ->assertSuccessful();

    expect($resolvedFor)->toBe(['acme'])
        ->and(WebhookCall::count())->toBe(1);
});

it('rejects a request signed with the config secret when a resolver is set', function () {
    OmiseWebhooks::resolveSigningSecretUsing(fn () => base64_encode('tenant_secret'));

    $this->postJson('omise/webhooks', $this->payload, omiseHeaders($this->payload, 'current_secret'))
        ->assertStatus(403);

    expect(WebhookCall::count())->toBe(0);
});

it('rejects a request when the resolver returns no secret', function () {
    OmiseWebhooks::resolveSigningSecretUsing(fn () => null);

    $this->postJson('omise/webhooks/acme', $this->payload, omiseHeaders($this->payload, ''))
        ->assertStatus(403);

    expect(WebhookCall::count())->toBe(0);
});

describe('standalone verification', function () {
    beforeEach(function () {
        config(['omise-webhooks.verify_signature' => false]);
        $this->body = json_encode($this->payload);
        $this->secret = base64_encode('current_secret');
    });

    function omiseRequest(string $body, array $headers): Request
    {
        $server = collect($headers)->mapWithKeys(fn ($value, $name) => ['HTTP_'.strtoupper(str_replace('-', '_', $name)) => $value])->all();

        return Request::create('/', 'POST', server: $server, content: $body);
    }

    it('verifies a request without reading the config', function () {
        $request = omiseRequest($this->body, omiseHeaders($this->payload, 'current_secret'));

        expect(OmiseSignatureValidator::verify($request, $this->secret))->toBeTrue()
            ->and(OmiseSignatureValidator::verify($request, base64_encode('wrong_secret')))->toBeFalse();
    });

    it('verifies a raw payload', function () {
        $headers = omiseHeaders($this->payload, ['old_secret', 'current_secret']);

        expect(OmiseSignatureValidator::verifySignature(
            $this->body, $headers['Omise-Signature'], $headers['Omise-Signature-Timestamp'], $this->secret,
        ))->toBeTrue();
    });

    it('fails with a reason', function (?string $signature, ?string $timestamp, string $secret, int $tolerance, string $reason) {
        $headers = omiseHeaders($this->payload, 'current_secret', $timestamp && ctype_digit($timestamp) ? (int) $timestamp : null);

        expect(fn () => OmiseSignatureValidator::assertValid(
            $this->body, $signature ?? $headers['Omise-Signature'], $timestamp, $secret, $tolerance,
        ))->toThrow(InvalidOmiseSignature::class, $reason);
    })->with([
        'missing signature' => ['', (string) time(), 'Y3VycmVudF9zZWNyZXQ=', 300, 'header is missing'],
        'missing timestamp' => [null, null, 'Y3VycmVudF9zZWNyZXQ=', 300, 'header is missing'],
        'non-numeric timestamp' => [null, 'abc', 'Y3VycmVudF9zZWNyZXQ=', 300, 'not a valid timestamp'],
        'expired timestamp' => [null, (string) (time() - 301), 'Y3VycmVudF9zZWNyZXQ=', 300, 'outside the allowed tolerance'],
        'empty secret' => [null, (string) time(), '', 300, 'empty or not valid base64'],
        'invalid base64 secret' => [null, (string) time(), '***', 300, 'empty or not valid base64'],
        'wrong secret' => [null, (string) time(), 'd3Jvbmdfc2VjcmV0', 300, 'does not match'],
    ]);

    it('ignores the verify_signature config', function () {
        expect(OmiseSignatureValidator::verify(omiseRequest($this->body, []), $this->secret))->toBeFalse();
    });
});

describe('ip verification', function () {
    beforeEach(function () {
        config([
            'omise-webhooks.verify_ip' => true,
            'omise-webhooks.verify_signature' => false,
        ]);
    });

    it('accepts a request from an ip in the published list', function () {
        Http::fake(['cdn.omise.co/*' => Http::response(['webhooks' => ['203.0.113.10']])]);

        $this->withServerVariables(['REMOTE_ADDR' => '203.0.113.10'])
            ->postJson('omise/webhooks', $this->payload)
            ->assertSuccessful();
    });

    it('rejects a request from an unknown ip', function () {
        Http::fake(['cdn.omise.co/*' => Http::response(['webhooks' => ['203.0.113.10']])]);

        $this->withServerVariables(['REMOTE_ADDR' => '198.51.100.1'])
            ->postJson('omise/webhooks', $this->payload)
            ->assertStatus(403);
    });

    it('falls back to the known ips when the ip list cannot be fetched', function (Closure $response) {
        Http::fake(['cdn.omise.co/*' => $response]);

        $this->withServerVariables(['REMOTE_ADDR' => '54.169.118.227'])
            ->postJson('omise/webhooks', $this->payload)
            ->assertSuccessful();
    })->with([
        'connection error' => [fn () => fn () => throw new ConnectionException('timeout')],
        'server error' => [fn () => fn () => Http::response(status: 503)],
        'malformed response' => [fn () => fn () => Http::response(['webhooks' => 'nope'])],
    ]);
});
