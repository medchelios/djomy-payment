<?php

namespace Tmoh\DjomyPayment\Endpoints;

final class DjomyEndpoints
{
    public const AUTHENTICATE = '/v1/auth';

    public const PAYMENT_LINKS = '/v1/links';

    public const PAYMENTS = '/v1/payments';

    public const PAYMENT_GATEWAY = self::PAYMENTS.'/gateway';

    public static function paymentLink(string $reference): string
    {
        return self::PAYMENT_LINKS.'/'.rawurlencode($reference);
    }

    public static function paymentStatus(string $transactionId): string
    {
        return self::PAYMENTS.'/'.rawurlencode($transactionId).'/status';
    }

    public static function confirmOtp(string $transactionReference): string
    {
        return self::PAYMENTS.'/'.rawurlencode($transactionReference).'/confirmOTP';
    }
}
