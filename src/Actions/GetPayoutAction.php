<?php

namespace Tmoh\DjomyPayment\Actions;

use Tmoh\DjomyPayment\DjomyClient;
use Tmoh\DjomyPayment\Endpoints\DjomyEndpoints;

final class GetPayoutAction
{
    public function __construct(private readonly DjomyClient $client) {}

    public function execute(string $orderId, string $payoutId): array
    {
        return $this->client->get(DjomyEndpoints::payoutItem($orderId, $payoutId));
    }
}
