<?php

namespace Tmoh\DjomyPayment\Actions;

use Tmoh\DjomyPayment\DjomyClient;
use Tmoh\DjomyPayment\Endpoints\DjomyEndpoints;

final class ListPayoutsAction
{
    public function __construct(private readonly DjomyClient $client) {}

    /**
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function execute(string $orderId, array $query = []): array
    {
        return $this->client->get(DjomyEndpoints::payoutItems($orderId), $query);
    }
}
