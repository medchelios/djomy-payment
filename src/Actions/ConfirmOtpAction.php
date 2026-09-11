<?php

namespace Tmoh\DjomyPayment\Actions;

use Tmoh\DjomyPayment\DjomyClient;
use Tmoh\DjomyPayment\Endpoints\DjomyEndpoints;

final class ConfirmOtpAction
{
    public function __construct(private readonly DjomyClient $client) {}

    public function execute(string $transactionReference, string $otp): array
    {
        return $this->client->post(DjomyEndpoints::confirmOtp($transactionReference), [
            'oneTimePin' => $otp,
        ]);
    }
}
