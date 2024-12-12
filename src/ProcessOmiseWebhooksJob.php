<?php

namespace Soap\OmiseWebhooks;

use Soap\OmiseWebhooks\Exceptions\WebhookFailed;
use Spatie\WebhookClient\Jobs\ProcessWebhookJob;
use Spatie\WebhookClient\Models\WebhookCall;

class ProcessOmiseWebhookJob extends ProcessWebhookJob
{
    public function __construct(WebhookCall $webhookCall)
    {
        parent::__construct($webhookCall);
        $this->onConnection(config('omise-webhooks.connection'));
        $this->onQueue(config('omise-webhooks.queue'));
    }

    public function handle()
    {
        if (! isset($this->webhookCall->payload['type']) || $this->webhookCall->payload['type'] === '') {
            throw WebhookFailed::missingType($this->webhookCall);
        }

        event("omise-webhooks::{$this->webhookCall->payload['type']}", $this->webhookCall);

        $jobClass = $this->determineJobClass($this->webhookCall->payload['type']);

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
