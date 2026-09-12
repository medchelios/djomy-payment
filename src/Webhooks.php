<?php

namespace Tmoh\DjomyPayment;

use Tmoh\DjomyPayment\Exceptions\DjomyException;
use Tmoh\DjomyPayment\Webhooks\WebhookEvent;

/**
 * Verifies and parses incoming Djomy webhooks.
 *
 * The webhook endpoint is hosted by the merchant: each notification is a
 * POST request with an `X-Webhook-Signature: v1:<signature>` header. The
 * signature is the HMAC-SHA256 of the raw payload body computed with the
 * merchant client secret.
 */
final class Webhooks
{
    public function __construct(
        private readonly string $clientSecret,
        private readonly string $version = WebhookEvent::VERSION_V2,
    ) {}

    /**
     * Verifies the signature of an incoming webhook.
     *
     * When the signature does not match, the webhook must be rejected and the
     * processing stopped.
     */
    public function verify(string $rawBody, string $header): bool
    {
        return Signature::verifyWebhook($rawBody, $header, $this->clientSecret);
    }

    /**
     * Parses a webhook payload, rejecting it when the signature is invalid.
     *
     * @param  string  $rawBody  Raw request body (must be used as-is for the signature).
     * @param  string  $header  The `X-Webhook-Signature` header value.
     *
     * @throws DjomyException When the body is invalid JSON or the signature is invalid.
     */
    public function parse(string $rawBody, string $header): WebhookEvent
    {
        if (! $this->verify($rawBody, $header)) {
            throw new DjomyException('Invalid Djomy webhook signature.');
        }

        return WebhookEvent::fromRawBody($rawBody);
    }

    public function version(): string
    {
        return $this->version;
    }
}
