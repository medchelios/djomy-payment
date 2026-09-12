<?php

namespace Tmoh\DjomyPayment\Actions;

use Tmoh\DjomyPayment\DjomyClient;
use Tmoh\DjomyPayment\Endpoints\DjomyEndpoints;

final class RetryPayoutAction
{
    public function __construct(private readonly DjomyClient $client) {}

    public function execute(string $orderId, string $payoutId): array
    {
        return $this->client->post(DjomyEndpoints::retryPayout($orderId, $payoutId));
    }
}
