<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
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
        ->assertStatus(500);

    expect(WebhookCall::count())->toBe(0);
});

it('rejects a request without signature headers', function () {
    $this->postJson('omise/webhooks', $this->payload)->assertStatus(500);

    expect(WebhookCall::count())->toBe(0);
});

it('rejects a request whose payload was tampered with', function () {
    $headers = omiseHeaders($this->payload, 'current_secret');

    $this->postJson('omise/webhooks', [...$this->payload, 'name' => 'changed'], $headers)
        ->assertStatus(500);

    expect(WebhookCall::count())->toBe(0);
});

it('rejects a request with an expired timestamp', function () {
    $headers = omiseHeaders($this->payload, 'current_secret', time() - 301);

    $this->postJson('omise/webhooks', $this->payload, $headers)->assertStatus(500);

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
