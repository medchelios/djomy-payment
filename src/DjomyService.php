<?php

namespace Tmoh\DjomyPayment;

use Tmoh\DjomyPayment\Actions\ConfirmOtpAction;
use Tmoh\DjomyPayment\Actions\GeneratePaymentLinkAction;
use Tmoh\DjomyPayment\Actions\GetPaymentLinkAction;
use Tmoh\DjomyPayment\Actions\GetPaymentStatusAction;
use Tmoh\DjomyPayment\Actions\GetPayoutAction;
use Tmoh\DjomyPayment\Actions\GetPayoutOrderAction;
use Tmoh\DjomyPayment\Actions\InitiateDirectPaymentAction;
use Tmoh\DjomyPayment\Actions\InitiatePayoutAction;
use Tmoh\DjomyPayment\Actions\InitiatePortalPaymentAction;
use Tmoh\DjomyPayment\Actions\ListPaymentLinksAction;
use Tmoh\DjomyPayment\Actions\ListPaymentsAction;
use Tmoh\DjomyPayment\Actions\ListPayoutOrdersAction;
use Tmoh\DjomyPayment\Actions\ListPayoutsAction;
use Tmoh\DjomyPayment\Actions\RetryPayoutAction;

/**
 * High-level service for Djomy payment operations.
 */
final class DjomyService
{
    public function __construct(private readonly DjomyClient $client) {}

    /**
     * Retrieves detailed information about a payment link by reference.
     *
     * 200 Retrieved | 400 Invalid format | 401 Missing or invalid API key
     * 403 Forbidden | 404 Not found | 429 Too many requests | 500 Internal server error
     *
     * @return array<mixed>
     */
    public function getPaymentLink(string $reference): array
    {
        return (new GetPaymentLinkAction($this->client))->execute($reference);
    }

    /**
     * Generates a payment link.
     *
     * @param  array<string, mixed>  $params
     * @return array<mixed>
     */
    public function generatePaymentLink(array $params): array
    {
        return (new GeneratePaymentLinkAction($this->client))->execute($params);
    }

    public function listPaymentLinks(): array
    {
        return (new ListPaymentLinksAction($this->client))->execute();
    }

    /**
     * Initiates a direct payment without redirecting the payer to Djomy.
     *
     * @param  array<string, mixed>  $params
     * @return array<mixed>
     */
    public function initiateDirectPayment(array $params): array
    {
        return (new InitiateDirectPaymentAction($this->client))->execute($params);
    }

    /**
     * Initiates a payment through the Djomy payment portal.
     *
     * @param  array<string, mixed>  $params
     * @return array<mixed>
     */
    public function initiatePortalPayment(array $params): array
    {
        return (new InitiatePortalPaymentAction($this->client))->execute($params);
    }

    /**
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function getAllPayments(array $query = []): array
    {
        return (new ListPaymentsAction($this->client))->execute($query);
    }

    public function getPaymentStatus(string $transactionId): array
    {
        return (new GetPaymentStatusAction($this->client))->execute($transactionId);
    }

    public function confirmOtp(string $transactionReference, string $otp): array
    {
        return (new ConfirmOtpAction($this->client))->execute($transactionReference, $otp);
    }

    /**
     * Retrieves a payout item of an order.
     *
     * 200 Retrieved | 401 Missing or invalid API key
     * 404 Payout not found | 500 Internal server error
     *
     * @return array<mixed>
     */
    public function getPayout(string $orderId, string $payoutId): array
    {
        return (new GetPayoutAction($this->client))->execute($orderId, $payoutId);
    }

    /**
     * Re-submits a failed payout to the provider.
     *
     * 200 Sent for retry | 401 Missing or invalid API key
     * 404 Payout not found | 409 Transaction already in progress
     * 422 Payout not eligible or insufficient balance | 500 Internal server error
     *
     * @return array<mixed>
     */
    public function retryPayout(string $orderId, string $payoutId): array
    {
        return (new RetryPayoutAction($this->client))->execute($orderId, $payoutId);
    }

    /**
     * Retrieves the paginated payouts of an order.
     *
     * 200 Retrieved | 401 Missing or invalid API key
     * 404 Order not found | 500 Internal server error
     *
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function getPayouts(string $orderId, array $query = []): array
    {
        return (new ListPayoutsAction($this->client))->execute($orderId, $query);
    }

    /**
     * Creates a payout order and its payouts.
     *
     * 200 Dry-run computed, nothing created | 201 Order created
     * 400 Invalid creation data | 401 Missing or invalid API key
     * 422 Untreatable data | 500 Internal server error
     *
     * @param  array<string, mixed>  $payload
     * @return array<mixed>
     */
    public function initiatePayout(array $payload, bool $dryRun = false): array
    {
        return (new InitiatePayoutAction($this->client))->execute($payload, $dryRun);
    }

    /**
     * Retrieves a payout order by identifier or reference.
     *
     * 200 Retrieved | 401 Missing or invalid API key
     * 404 Order not found | 500 Internal server error
     *
     * @return array<mixed>
     */
    public function getPayoutOrder(string $orderId): array
    {
        return (new GetPayoutOrderAction($this->client))->execute($orderId);
    }

    /**
     * Retrieves the paginated payout orders of the merchant.
     *
     * 200 Retrieved | 401 Missing or invalid API key | 500 Internal server error
     *
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function getPayoutOrders(array $query = []): array
    {
        return (new ListPayoutOrdersAction($this->client))->execute($query);
    }

    public function getClient(): DjomyClient
    {
        return $this->client;
    }
}
