<?php

namespace Soap\OmiseWebhooks;

use Soap\OmiseWebhooks\Exceptions\WebhookFailed;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;
use Spatie\WebhookClient\Models\WebhookCall;

class ProcessOmiseWebhooksJob extends ProcessWebhookJob
{
    public function __construct(WebhookCall $webhookCall)
    {
        parent::__construct($webhookCall);
        $this->onConnection(config('omise-webhooks.connection'));
        $this->onQueue(config('omise-webhooks.queue'));
    }

    public function handle()
    {
        if (! isset($this->webhookCall->payload['key']) || $this->webhookCall->payload['key'] === '') {
            throw WebhookFailed::missingType($this->webhookCall);
        }

        event("omise-webhooks::{$this->webhookCall->payload['key']}", $this->webhookCall);

        $jobClass = $this->determineJobClass($this->webhookCall->payload['key']);

        if ($jobClass === '') {
            return;
        }

        if (! class_exists($jobClass)) {
            throw WebhookFailed::jobClassDoesNotExist($jobClass, $this->webhookCall);
        }

        dispatch(new $jobClass($this->webhookCall));
    }

    protected function determineJobClass(string $eventType): string
    {
        $jobConfigKey = str_replace('.', '_', $eventType);

        $defaultJob = config('omise-webhooks.default_job', '');

        return config("omise-webhooks.jobs.{$jobConfigKey}", $defaultJob);
    }
}
