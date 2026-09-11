<?php

namespace Tmoh\DjomyPayment;

use Tmoh\DjomyPayment\Actions\ConfirmOtpAction;
use Tmoh\DjomyPayment\Actions\GeneratePaymentLinkAction;
use Tmoh\DjomyPayment\Actions\GetPaymentLinkAction;
use Tmoh\DjomyPayment\Actions\GetPaymentStatusAction;
use Tmoh\DjomyPayment\Actions\InitiateDirectPaymentAction;
use Tmoh\DjomyPayment\Actions\InitiatePortalPaymentAction;
use Tmoh\DjomyPayment\Actions\ListPaymentLinksAction;

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

    public function initiatePortalPayment(array $params): array
    {
        return (new InitiatePortalPaymentAction($this->client))->execute($params);
    }

    public function getPaymentStatus(string $transactionId): array
    {
        return (new GetPaymentStatusAction($this->client))->execute($transactionId);
    }

    public function confirmOtp(string $transactionReference, string $otp): array
    {
        return (new ConfirmOtpAction($this->client))->execute($transactionReference, $otp);
    }

    public function getClient(): DjomyClient
    {
        return $this->client;
    }
}
