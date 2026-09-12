<?php

namespace Tmoh\DjomyPayment\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Tmoh\DjomyPayment\DjomyServiceProvider;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [DjomyServiceProvider::class];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('djomy.base_url', 'https://api.djomy.com');
        $app['config']->set('djomy.client_id', 'test-client-id');
        $app['config']->set('djomy.client_secret', 'test-client-secret');
        $app['config']->set('djomy.webhook_version', 'v2');
    }
}
