<?php

use Illuminate\Support\Facades\Event;
use Soap\OmiseWebhooks\ProcessOmiseWebhooksJob;
use Soap\OmiseWebhooks\Tests\DummyJob;
use Spatie\WebhookClient\Models\WebhookCall;

beforeEach(function () {
    Event::fake();
    config(['omise-webhooks.jobs' => ['my_key' => DummyJob::class]]);

    $this->webhookCall = WebhookCall::create([
        'name' => 'omise',
        'url' => '/omise',
        'payload' => ['key' => 'my.key', 'name' => 'value'],
    ]);

    $this->processOmiseWebhooksJob = new ProcessOmiseWebhooksJob($this->webhookCall);
});

it('can dispatch the configured job', function () {
    $this->processOmiseWebhooksJob->handle();

    Event::assertDispatched('omise-webhooks::my.key', function ($event, $payload) {
        return $this->webhookCall->id == cache('dummyjob')->id;
    });
});

it('will not dispatch a job if the job class does not exists', function () {
    config(['omise-webhooks.jobs' => ['another_key' => DummyJob::class]]);

    $this->processOmiseWebhooksJob->handle();

    $this->assertNull(cache('dummyjob'));
});

it('will not dispatch a job if the job class is not configured', function () {
    config(['omise-webhooks.jobs' => []]);

    $this->processOmiseWebhooksJob->handle();

    $this->assertNull(cache('dummyjob'));
});

it('will dispatch the default job even when no corresponding job is configured', function () {
    config(['omise-webhooks.jobs' => ['another_key' => DummyJob::class]]);

    $this->processOmiseWebhooksJob->handle();

    $webhookCall = $this->webhookCall;

    Event::assertDispatched('omise-webhooks::my.key', function ($event, $payload) use ($webhookCall) {
        $this->assertInstanceof(WebhookCall::class, $payload);
        $this->assertEquals($webhookCall->id, $payload->id);

        return true;
    });

    $this->assertNull(cache('dummyjob'));
});

it('can specify a connection in the config file', function () {
    config(['omise-webhooks.connection' => 'my_connection']);

    $processOmiseWebhooksJob = new ProcessOmiseWebhooksJob($this->webhookCall);

    $this->assertEquals('my_connection', $processOmiseWebhooksJob->connection);
});

it('can specify a queue in the config file', function () {
    config(['omise-webhooks.queue' => 'my_queue']);

    $processOmiseWebhooksJob = new ProcessOmiseWebhooksJob($this->webhookCall);

    $this->assertEquals('my_queue', $processOmiseWebhooksJob->queue);
});
