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

    /**
     * Compute the signature value for a webhook payload.
     *
     * The result is the hexadecimal HMAC-SHA256 of the raw payload body,
     * without the `v1:` header prefix.
     */
    public static function webhookSignature(string $rawBody, string $clientSecret): string
    {
        return self::hash($rawBody, $clientSecret);
    }

    /**
     * Verify the `X-Webhook-Signature` header (`v1:<signature>`).
     *
     * Returns true only when the provided header matches the expected
     * HMAC-SHA256 signature computed from the raw payload and client secret.
     */
    public static function verifyWebhook(string $rawBody, string $header, string $clientSecret): bool
    {
        $provided = self::extractSignatureFromHeader($header);

        if ($provided === null) {
            return false;
        }

        return hash_equals(self::webhookSignature($rawBody, $clientSecret), $provided);
    }

    /**
     * Strips the version prefix from a webhook signature header.
     *
     * `v1:a1b2c3...` becomes `a1b2c3...`.
     */
    public static function extractSignatureFromHeader(string $header): ?string
    {
        $header = trim($header);

        if (! str_contains($header, ':')) {
            return $header === '' ? null : $header;
        }

        $signature = substr($header, strpos($header, ':') + 1);

        return $signature === '' ? null : $signature;
    }
}
