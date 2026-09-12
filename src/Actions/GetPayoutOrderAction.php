<?php

namespace Tmoh\DjomyPayment\Actions;

use Tmoh\DjomyPayment\DjomyClient;
use Tmoh\DjomyPayment\Endpoints\DjomyEndpoints;

final class GetPayoutOrderAction
{
    public function __construct(private readonly DjomyClient $client) {}

    public function execute(string $orderId): array
    {
        return $this->client->get(DjomyEndpoints::payoutOrder($orderId));
    }
}
