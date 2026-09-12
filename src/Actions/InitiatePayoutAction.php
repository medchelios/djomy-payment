<?php

namespace Tmoh\DjomyPayment\Actions;

use Tmoh\DjomyPayment\DjomyClient;
use Tmoh\DjomyPayment\Endpoints\DjomyEndpoints;

final class InitiatePayoutAction
{
    public function __construct(private readonly DjomyClient $client) {}

    /**
     * @param  array<string, mixed>  $payload
     * @return array<mixed>
     */
    public function execute(array $payload, bool $dryRun = false): array
    {
        return $this->client->post(
            DjomyEndpoints::PAYOUT_ORDERS,
            $payload,
            $dryRun ? ['dryRun' => true] : [],
        );
    }
}
