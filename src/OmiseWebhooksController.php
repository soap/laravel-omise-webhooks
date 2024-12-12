<?php

namespace Soap\OmiseWebhooks;

use Illuminate\Http\Request;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\WebhookProcessor;

class StripeWebhooksController
{
    public function __invoke(Request $request, ?string $configKey = null)
    {
        $webhookConfig = new WebhookConfig([
            'name' => 'omise',
            'signing_secret' => ($configKey) ?
                config('omise-webhooks.signing_secret_'.$configKey) :
                config('omise-webhooks.signing_secret'),
            'signature_header_name' => 'Omise-Signature',
            'signature_validator' => OmiseSignatureValidator::class,
            'webhook_profile' => config('omise-webhooks.profile'),
            'webhook_model' => config('omise-webhooks.model'),
            'process_webhook_job' => ProcessOmiseWebhookJob::class,
        ]);

        return (new WebhookProcessor($request, $webhookConfig))->process();
    }
}
