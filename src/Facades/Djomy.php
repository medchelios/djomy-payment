<?php

namespace Tmoh\DjomyPayment\Facades;

use Illuminate\Support\Facades\Facade;
use Tmoh\DjomyPayment\DjomyClient;

/**
 * @method static array getPaymentLink(string $reference)
 * @method static string authenticate()
 * @method static DjomyClient getClient()
 */
class Djomy extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return 'djomy';
    }
}
