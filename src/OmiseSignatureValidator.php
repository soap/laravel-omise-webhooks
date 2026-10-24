<?php

namespace Soap\OmiseWebhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Spatie\WebhookClient\Exceptions\InvalidConfig;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;

class OmiseSignatureValidator implements SignatureValidator
{
    public const TIMESTAMP_HEADER = 'Omise-Signature-Timestamp';

    public function isValid(Request $request, WebhookConfig $config): bool
    {
        if (config('omise-webhooks.verify_ip') && ! in_array($request->ip(), $this->getWebhookIps())) {
            return false;
        }

        if (! config('omise-webhooks.verify_signature')) {
            return true;
        }

        return $this->hasValidSignature($request, $config);
    }

    /**
     * Verify the request following https://docs.omise.co/api-webhooks#webhook-signature-verification
     *
     * The signed payload is "<timestamp>.<raw body>", signed with HMAC-SHA256 using the
     * base64-decoded webhook secret. During secret rotation, the signature header holds
     * two comma-separated signatures, either of which may match.
     */
    protected function hasValidSignature(Request $request, WebhookConfig $config): bool
    {
        $signatureHeader = $request->header($config->signatureHeaderName);
        $timestamp = $request->header(self::TIMESTAMP_HEADER);

        if (! $signatureHeader || ! $timestamp || ! ctype_digit($timestamp)) {
            return false;
        }

        if (empty($config->signingSecret)) {
            throw InvalidConfig::signingSecretNotSet();
        }

        $tolerance = (int) config('omise-webhooks.signature_tolerance', 300);

        if ($tolerance > 0 && abs(time() - (int) $timestamp) > $tolerance) {
            return false;
        }

        $secret = base64_decode($config->signingSecret, true);

        if ($secret === false) {
            return false;
        }

        $computedSignature = hash_hmac('sha256', $timestamp.'.'.$request->getContent(), $secret);

        foreach (explode(',', $signatureHeader) as $signature) {
            if (hash_equals($computedSignature, trim($signature))) {
                return true;
            }
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
