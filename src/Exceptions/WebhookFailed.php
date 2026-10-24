<?php

namespace Soap\OmiseWebhooks\Exceptions;

use Exception;
use Spatie\WebhookClient\Models\WebhookCall;

class WebhookFailed extends Exception
{
    public static function jobClassDoesNotExist(string $jobClass, WebhookCall $webhookCall): self
    {
        return new static("Could not process webhook id `{$webhookCall->id}` of type `{$webhookCall->payload['key']}` because the configured jobclass `$jobClass` does not exist.");
    }

    public static function missingType(WebhookCall $webhookCall): self
    {
        return new static("Webhook call id `{$webhookCall->id}` did not contain a key. Valid Omise webhook calls should always contain an event key.");
    }

    public function render($request)
    {
        return response(['error' => $this->getMessage()], 400);
    }
}
