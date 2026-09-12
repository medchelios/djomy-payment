<?php

namespace Tmoh\DjomyPayment\Facades;

use Illuminate\Support\Facades\Facade;
use Tmoh\DjomyPayment\Webhooks\WebhookEvent;

/**
 * @method static bool verify(string $rawBody, string $header)
 * @method static WebhookEvent parse(string $rawBody, string $header)
 * @method static string version()
 */
class DjomyWebhook extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'djomy-webhooks';
    }
}
