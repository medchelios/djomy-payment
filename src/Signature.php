<?php

namespace Tmoh\DjomyPayment;

/**
 * Generates the HMAC-SHA256 signatures required by the Djomy Payment Platform API.
 *
 * Every request must be signed with the client secret and sent through the
 * X-API-KEY header as: `X-API-KEY: <clientId>:<hmac-signature>`.
 */
final class Signature
{
    /**
     * Compute the hexadecimal HMAC-SHA256 signature of a string.
     */
    public static function hash(string $stringToSign, string $clientSecret): string
    {
        return hash_hmac('sha256', $stringToSign, $clientSecret);
    }

    /**
     * Build the X-API-KEY header value for the given credentials.
     *
     * @return string `<clientId>:<hmac-signature>`
     */
    public static function apiKeyHeaderValue(string $clientId, string $clientSecret): string
    {
        return $clientId.':'.self::hash($clientId, $clientSecret);
    }
}
