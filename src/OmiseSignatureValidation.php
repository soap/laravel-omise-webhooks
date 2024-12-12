<?php

namespace Soap\OmiseWebhooks;

use Exception;
use Illuminate\Http\Request;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

class OmiseSignatureValidator implements SignatureValidator
{
    public function isValid(Request $request, WebhookConfig $config): bool
    {
        if (! config('omise-webhooks.verify_signature')) {
            return true;
        }

        $signature = $request->header('-Signature');
        $secret = $config->signingSecret;

        /*
        try {
            //Webhook::constructEvent($request->getContent(), $signature, $secret);
        } catch (Exception) {
            return false;
        }
        */

        return true;
    }
}
