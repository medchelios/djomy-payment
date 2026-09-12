<?php

use Tmoh\DjomyPayment\Exceptions\DjomyException;
use Tmoh\DjomyPayment\Signature;
use Tmoh\DjomyPayment\Webhooks;
use Tmoh\DjomyPayment\Webhooks\WebhookEvent;

function djomyWebhookBody(): string
{
    return (string) json_encode([
        'message' => 'Statut du paiement',
        'eventType' => 'payment.success',
        'eventId' => '8bfd5709-737c-4254-99a7-57a3d630b349',
        'data' => [
            'payment' => [
                'transactionId' => '123e4567-e89b-12d3-a456-426614174000',
                'status' => 'SUCCESS',
                'paidAmount' => 10000,
                'currency' => 'GNF',
            ],
        ],
        'paymentLinkReference' => 'LINK-REF',
        'timestamp' => '2025-07-13T10:31:00.000Z',
    ]);
}

describe('Signature::webhookSignature', function () {
    it('computes the HMAC-SHA256 of the raw payload', function () {
        $body = djomyWebhookBody();
        $secret = 'test-client-secret';

        expect(Signature::webhookSignature($body, $secret))
            ->toBe(hash_hmac('sha256', $body, $secret));
    });

    it('extracts the signature from the v1 header prefix', function () {
        expect(Signature::extractSignatureFromHeader('v1:abc123'))->toBe('abc123');
        expect(Signature::extractSignatureFromHeader(' abc123 '))->toBe('abc123');
        expect(Signature::extractSignatureFromHeader('v1:'))->toBeNull();
        expect(Signature::extractSignatureFromHeader(''))->toBeNull();
    });

    it('verifies a valid webhook signature', function () {
        $body = djomyWebhookBody();
        $secret = 'test-client-secret';
        $header = 'v1:'.hash_hmac('sha256', $body, $secret);

        expect(Signature::verifyWebhook($body, $header, $secret))->toBeTrue();
    });

    it('rejects a tampered payload', function () {
        $body = djomyWebhookBody();
        $secret = 'test-client-secret';
        $header = 'v1:'.hash_hmac('sha256', $body, $secret);

        expect(Signature::verifyWebhook($body.' ', $header, $secret))->toBeFalse();
    });

    it('rejects a wrong secret', function () {
        $body = djomyWebhookBody();
        $header = 'v1:'.hash_hmac('sha256', $body, 'other-secret');

        expect(Signature::verifyWebhook($body, $header, 'test-client-secret'))->toBeFalse();
    });
});

describe('Webhooks', function () {
    it('parses a webhook once the signature is verified', function () {
        $body = djomyWebhookBody();
        $header = 'v1:'.hash_hmac('sha256', $body, 'secret');
        $webhooks = new Webhooks('secret');

        $event = $webhooks->parse($body, $header);

        expect($event)->toBeInstanceOf(WebhookEvent::class);
        expect($event->eventType())->toBe('payment.success');
        expect($event->eventId())->toBe('8bfd5709-737c-4254-99a7-57a3d630b349');
        expect($event->paymentLinkReference())->toBe('LINK-REF');
        expect($event->payment()['transactionId'])->toBe('123e4567-e89b-12d3-a456-426614174000');
        expect($event->isPaymentEvent())->toBeTrue();
        expect($event->isPayoutEvent())->toBeFalse();
    });

    it('rejects an invalid signature', function () {
        $body = djomyWebhookBody();
        $webhooks = new Webhooks('secret');

        expect(fn () => $webhooks->parse($body, 'v1:invalid'))
            ->toThrow(DjomyException::class, 'Invalid Djomy webhook signature.');
    });

    it('defaults to the v2 payload version', function () {
        expect((new Webhooks('secret'))->version())->toBe(WebhookEvent::VERSION_V2);
    });
});

describe('WebhookEvent', function () {
    it('reads V1 payloads where payment data sits directly in data', function () {
        $event = WebhookEvent::fromArray([
            'eventType' => 'payment.success',
            'data' => ['transactionId' => 'txn_v1', 'status' => 'SUCCESS'],
        ]);

        expect($event->data(WebhookEvent::VERSION_V1)['transactionId'])->toBe('txn_v1');
    });

    it('reads V2 payout payloads nested under data.payout', function () {
        $event = WebhookEvent::fromArray([
            'eventType' => 'payout.success',
            'data' => ['payout' => ['transactionId' => 'txn_payout']],
        ]);

        expect($event->isPayoutEvent())->toBeTrue();
        expect($event->payout()['transactionId'])->toBe('txn_payout');
    });

    it('returns an empty array when the nested data is absent', function () {
        $event = WebhookEvent::fromArray(['eventType' => 'payment.pending']);

        expect($event->payment())->toBe([]);
        expect($event->metadata())->toBe([]);
    });

    it('throws when the raw body is not a valid JSON object', function () {
        expect(fn () => WebhookEvent::fromRawBody('not-json'))
            ->toThrow(DjomyException::class);
    });

    it('keeps the raw body for signature verification', function () {
        $body = djomyWebhookBody();
        $event = WebhookEvent::fromRawBody($body);

        expect($event->rawBody())->toBe($body);
    });
});
