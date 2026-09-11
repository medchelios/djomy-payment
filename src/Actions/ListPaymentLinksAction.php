<?php

namespace Tmoh\DjomyPayment\Actions;

use Tmoh\DjomyPayment\DjomyClient;
use Tmoh\DjomyPayment\Endpoints\DjomyEndpoints;

final class ListPaymentLinksAction
{
    public function __construct(private readonly DjomyClient $client) {}

    public function execute(): array
    {
        return $this->client->get(DjomyEndpoints::PAYMENT_LINKS);
    }
}
