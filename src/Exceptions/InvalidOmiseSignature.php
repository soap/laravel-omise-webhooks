<?php

namespace Soap\OmiseWebhooks\Exceptions;

use Exception;

class InvalidOmiseSignature extends Exception
{
    public static function missingHeaders(): self
    {
        return new self('The Omise-Signature or Omise-Signature-Timestamp header is missing.');
    }

    public static function invalidTimestamp(): self
    {
        return new self('The Omise-Signature-Timestamp header is not a valid timestamp.');
    }

    public static function timestampOutsideTolerance(): self
    {
        return new self('The Omise-Signature-Timestamp header is outside the allowed tolerance.');
    }

    public static function invalidSecret(): self
    {
        return new self('The signing secret is empty or not valid base64.');
    }

    public static function signatureMismatch(): self
    {
        return new self('The signature does not match.');
    }
}
