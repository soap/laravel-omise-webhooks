<?php

namespace Soap\OmiseWebhooks;

use Closure;
use Illuminate\Http\Request;

class OmiseWebhooks
{
    /**
     * @var (Closure(Request, ?string): ?string)|null
     */
    protected static ?Closure $signingSecretResolver = null;

    /**
     * Resolve the (base64-encoded) signing secret at runtime instead of reading it from the config,
     * e.g. to look up the secret of a tenant's Omise account by the `{configKey}` route parameter.
     * Returning null or an empty string rejects the request.
     *
     * @param  (Closure(Request, ?string): ?string)|null  $resolver
     */
    public static function resolveSigningSecretUsing(?Closure $resolver): void
    {
        static::$signingSecretResolver = $resolver;
    }

    public static function signingSecret(Request $request, ?string $configKey = null): ?string
    {
        if (static::$signingSecretResolver) {
            return (static::$signingSecretResolver)($request, $configKey);
        }

        return $configKey
            ? config('omise-webhooks.signing_secret_'.$configKey)
            : config('omise-webhooks.signing_secret');
    }

    public static function flushState(): void
    {
        static::$signingSecretResolver = null;
    }
}
