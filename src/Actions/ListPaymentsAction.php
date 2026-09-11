<?php

namespace Tmoh\DjomyPayment\Actions;

use Tmoh\DjomyPayment\DjomyClient;
use Tmoh\DjomyPayment\Endpoints\DjomyEndpoints;

final class ListPaymentsAction
{
    public function __construct(private readonly DjomyClient $client) {}

    /**
     * @param  array<string, mixed>  $query
     * @return array<mixed>
     */
    public function execute(array $query = []): array
    {
        return $this->client->get(DjomyEndpoints::PAYMENTS, $query);
    }
}
