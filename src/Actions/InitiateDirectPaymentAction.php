<?php

namespace Tmoh\DjomyPayment\Actions;

use Tmoh\DjomyPayment\DjomyClient;
use Tmoh\DjomyPayment\Endpoints\DjomyEndpoints;

final class InitiateDirectPaymentAction
{
    public function __construct(private readonly DjomyClient $client) {}

    public function execute(array $payload): array
    {
        return $this->client->post(DjomyEndpoints::PAYMENTS, $payload);
    }
}
