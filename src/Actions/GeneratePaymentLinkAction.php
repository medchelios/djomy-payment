<?php

namespace Tmoh\DjomyPayment\Actions;

use Tmoh\DjomyPayment\DjomyClient;
use Tmoh\DjomyPayment\Endpoints\DjomyEndpoints;

final class GeneratePaymentLinkAction
{
    public function __construct(private readonly DjomyClient $client) {}

    /** @param array<string, mixed> $payload */
    public function execute(array $payload): array
    {
        return $this->client->post(DjomyEndpoints::PAYMENT_LINKS, $payload);
    }
}
