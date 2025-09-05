<?php

namespace Soap\OmiseWebhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

class OmiseSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        if (! config('omise-webhooks.verify_ip')) {
            return true;
        }

        // $signature = $request->header('-Signature');
        // $secret = $config->signingSecret;
        if (in_array($request->ip(), $this->getWebhookIps())) {
            return true;
        }

        return false;
    }

    protected function getWebhookIps(): array
    {
        $defindedIps = [
            '54.169.118.227',
            '52.74.199.175',
            '18.139.13.19',
        ];

        $response = Http::get('https://cdn.omise.co/ips.json');
        if ($response->ok()) {
            return $response->json('webhooks', $defindedIps);
        }

        return $defindedIps;
    }
}
