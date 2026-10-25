<?php

namespace Soap\OmiseWebhooks;

use Illuminate\Http\Request;
use Spatie\WebhookClient\Exceptions\InvalidWebhookSignature;
use Spatie\WebhookClient\WebhookConfig;
use Spatie\WebhookClient\WebhookProcessor;

class OmiseWebhooksController
{
    public function __invoke(Request $request, ?string $configKey = null)
    {
        $webhookConfig = new WebhookConfig([
            'name' => 'omise',
            'signing_secret' => (string) OmiseWebhooks::signingSecret($request, $configKey),
            'signature_header_name' => OmiseSignatureValidator::SIGNATURE_HEADER,
            'signature_validator' => OmiseSignatureValidator::class,
            'webhook_profile' => config('omise-webhooks.profile'),
            'webhook_model' => config('omise-webhooks.model'),
            'process_webhook_job' => config('omise-webhooks.process_webhook_job', ProcessOmiseWebhooksJob::class),
        ]);

        try {
            return (new WebhookProcessor($request, $webhookConfig))->process();
        } catch (InvalidWebhookSignature $exception) {
            return response(['error' => $exception->getMessage()], 403);
        }
    }
}
