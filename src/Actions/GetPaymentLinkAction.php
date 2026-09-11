<?php

namespace Tmoh\DjomyPayment\Actions;

use Tmoh\DjomyPayment\DjomyClient;
use Tmoh\DjomyPayment\Endpoints\DjomyEndpoints;

final class GetPaymentLinkAction
{
    public function __construct(private readonly DjomyClient $client) {}

    public function execute(string $reference): array
    {
        return $this->client->get(DjomyEndpoints::paymentLink($reference));
    }
}
