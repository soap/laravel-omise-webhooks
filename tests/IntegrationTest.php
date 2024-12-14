<?php

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Route;
use Soap\OmiseWebhooks\Tests\DummyJob;
use Spatie\WebhookClient\Models\WebhookCall;

beforeEach(function () {
    Event::fake();

    Route::omiseWebhooks('omise/webhooks');
    Route::omiseWebhooks('omise/webhooks/{configKey}');

    config(['omise-webhooks.jobs' => ['my_key' => DummyJob::class]]);
    cache()->clear();
});

it('can handle a valid request', function () {
    $this->withoutExceptionHandling();

    $payload = [
        'key' => 'my.key',
        'name' => 'value',
    ];
    $this->postJson('omise/webhooks', $payload)
        ->assertSuccessful();

    $this->assertCount(1, WebhookCall::get());
    $webhookCall = WebhookCall::first();

    $this->assertEquals('my.key', $webhookCall->payload['key']);
    $this->assertEquals($payload, $webhookCall->payload);
    $this->assertNull($webhookCall->exception);

    Event::assertDispatched('omise-webhooks::my.key', function ($event, $eventPayload) use ($webhookCall) {
        $this->assertInstanceOf(WebhookCall::class, $eventPayload);
        $this->assertEquals($webhookCall->id, $eventPayload->id);

        return true;
    });

    $this->assertEquals($webhookCall->id, cache('dummyjob')->id);
});
