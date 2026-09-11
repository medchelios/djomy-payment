<?php

namespace Tmoh\DjomyPayment\Actions;

use Tmoh\DjomyPayment\DjomyClient;
use Tmoh\DjomyPayment\Endpoints\DjomyEndpoints;

final class GetPaymentStatusAction
{
    public function __construct(private readonly DjomyClient $client) {}

    public function execute(string $transactionId): array
    {
        return $this->client->get(DjomyEndpoints::paymentStatus($transactionId));
    }
}
