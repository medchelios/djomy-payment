<?php

namespace Tmoh\DjomyPayment\Webhooks;

use Tmoh\DjomyPayment\Exceptions\DjomyException;

/**
 * Immutable representation of a Djomy webhook payload.
 *
 * Envelope fields (message, eventType, eventId, data, timestamp) are identical
 * across payload versions. The webhook configuration (V1 or V2) only changes
 * the structure of the `data` field.
 */
final class WebhookEvent
{
    public const VERSION_V1 = 'v1';

    public const VERSION_V2 = 'v2';

    public const PAYMENT_CREATED = 'payment.created';

    public const PAYMENT_REDIRECTED = 'payment.redirected';

    public const PAYMENT_PENDING = 'payment.pending';

    public const PAYMENT_CANCELLED = 'payment.cancelled';

    public const PAYMENT_TIMEOUT = 'payment.timeout';

    public const PAYMENT_SUCCESS = 'payment.success';

    public const PAYMENT_FAILED = 'payment.failed';

    public const PAYMENT_REFUNDED = 'payment.refunded';

    public const PAYOUT_SUCCESS = 'payout.success';

    public const PAYOUT_FAILED = 'payout.failed';

    /**
     * @param  array<string, mixed>  $payload
     */
    private function __construct(
        private readonly array $payload,
        private readonly string $rawBody,
    ) {}

    /**
     * Builds an event from a decoded payload and its raw JSON body.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function fromArray(array $payload, ?string $rawBody = null): self
    {
        return new self($payload, $rawBody ?? (string) json_encode($payload));
    }

    /**
     * Builds an event from a raw JSON body.
     */
    public static function fromRawBody(string $rawBody): self
    {
        $decoded = json_decode($rawBody, true);

        if (! is_array($decoded)) {
            throw new DjomyException('The webhook body is not a valid JSON object.');
        }

        return new self($decoded, $rawBody);
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return $this->payload;
    }

    /**
     * Raw body used to compute the signature.
     */
    public function rawBody(): string
    {
        return $this->rawBody;
    }

    public function message(): ?string
    {
        return $this->stringValue('message');
    }

    public function eventType(): ?string
    {
        return $this->stringValue('eventType');
    }

    public function eventId(): ?string
    {
        return $this->stringValue('eventId');
    }

    public function paymentLinkReference(): ?string
    {
        return $this->stringValue('paymentLinkReference');
    }

    public function timestamp(): ?string
    {
        return $this->stringValue('timestamp');
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        $metadata = $this->payload['metadata'] ?? [];

        return is_array($metadata) ? $metadata : [];
    }

    /**
     * Event data.
     *
     * In V1 the payment data is stored directly in `data`. In V2 the data is
     * nested under `data.payment` for payment events and `data.payout` for
     * payout events. The version argument disambiguates the two structures.
     *
     * @return array<string, mixed>
     */
    public function data(string $version = self::VERSION_V2): array
    {
        $data = $this->payload['data'] ?? [];

        if (! is_array($data)) {
            return [];
        }

        if ($version === self::VERSION_V1) {
            return $data;
        }

        $nested = $data['payment'] ?? $data['payout'] ?? null;

        return is_array($nested) ? $nested : [];
    }

    /**
     * @return array<string, mixed>
     */
    public function payment(): array
    {
        return $this->data(self::VERSION_V2);
    }

    /**
     * @return array<string, mixed>
     */
    public function payout(): array
    {
        return $this->data(self::VERSION_V2);
    }

    public function isPaymentEvent(): bool
    {
        return str_starts_with((string) $this->eventType(), 'payment.');
    }

    public function isPayoutEvent(): bool
    {
        return str_starts_with((string) $this->eventType(), 'payout.');
    }

    private function stringValue(string $key): ?string
    {
        $value = $this->payload[$key] ?? null;

        return is_string($value) ? $value : null;
    }
}
