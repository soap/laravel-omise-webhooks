<?php

namespace Soap\OmiseWebhooks;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Soap\OmiseWebhooks\Exceptions\InvalidOmiseSignature;
use Spatie\WebhookClient\SignatureValidator\SignatureValidator;
use Spatie\WebhookClient\WebhookConfig;
use Throwable;

class OmiseSignatureValidator implements SignatureValidator
{
    public const SIGNATURE_HEADER = 'Omise-Signature';

    public const TIMESTAMP_HEADER = 'Omise-Signature-Timestamp';

    public function isValid(Request $request, WebhookConfig $config): bool
    {
        if (config('omise-webhooks.verify_ip') && ! in_array($request->ip(), $this->getWebhookIps())) {
            return false;
        }

        if (! config('omise-webhooks.verify_signature', true)) {
            return true;
        }

        return static::verify($request, $config->signingSecret, (int) config('omise-webhooks.signature_tolerance', 300));
    }

    /**
     * Verify the signature of an Omise webhook request, independent of the package config.
     */
    public static function verify(Request $request, string $secret, int $tolerance = 300): bool
    {
        return static::verifySignature(
            $request->getContent(),
            $request->header(self::SIGNATURE_HEADER),
            $request->header(self::TIMESTAMP_HEADER),
            $secret,
            $tolerance,
        );
    }

    /**
     * Verify the signature of a raw Omise webhook payload, independent of the package config.
     */
    public static function verifySignature(string $rawBody, ?string $signatureHeader, ?string $timestamp, string $secret, int $tolerance = 300): bool
    {
        try {
            static::assertValid($rawBody, $signatureHeader, $timestamp, $secret, $tolerance);
        } catch (InvalidOmiseSignature) {
            return false;
        }

        return true;
    }

    /**
     * Verify the payload following https://docs.omise.co/api-webhooks#webhook-signature-verification
     *
     * The signed payload is "<timestamp>.<raw body>", signed with HMAC-SHA256 using the
     * base64-decoded webhook secret. During secret rotation, the signature header holds
     * two comma-separated signatures, either of which may match.
     * A tolerance of 0 disables the timestamp check.
     *
     * @throws InvalidOmiseSignature
     */
    public static function assertValid(string $rawBody, ?string $signatureHeader, ?string $timestamp, string $secret, int $tolerance = 300): void
    {
        if (! $signatureHeader || ! $timestamp) {
            throw InvalidOmiseSignature::missingHeaders();
        }

        if (! ctype_digit($timestamp)) {
            throw InvalidOmiseSignature::invalidTimestamp();
        }

        if ($tolerance > 0 && abs(time() - (int) $timestamp) > $tolerance) {
            throw InvalidOmiseSignature::timestampOutsideTolerance();
        }

        $decodedSecret = base64_decode($secret, true);

        if ($decodedSecret === false || $decodedSecret === '') {
            throw InvalidOmiseSignature::invalidSecret();
        }

        $computedSignature = hash_hmac('sha256', $timestamp.'.'.$rawBody, $decodedSecret);

        foreach (explode(',', $signatureHeader) as $signature) {
            if (hash_equals($computedSignature, trim($signature))) {
                return;
            }
        }

        throw InvalidOmiseSignature::signatureMismatch();
    }

    protected function getWebhookIps(): array
    {
        $defindedIps = [
            '54.169.118.227',
            '52.74.199.175',
            '18.139.13.19',
        ];

        try {
            $response = Http::timeout(5)->get('https://cdn.omise.co/ips.json');
        } catch (Throwable) {
            return $defindedIps;
        }

        $ips = $response->ok() ? $response->json('webhooks') : null;

        return is_array($ips) ? $ips : $defindedIps;
    }
}
