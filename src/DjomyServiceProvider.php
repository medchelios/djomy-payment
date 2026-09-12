<?php

namespace Tmoh\DjomyPayment;

use Illuminate\Support\ServiceProvider;
use Tmoh\DjomyPayment\Endpoints\DjomyEndpoints;
use Tmoh\DjomyPayment\Exceptions\ConfigurationException;
use Tmoh\DjomyPayment\Webhooks\WebhookEvent;

class DjomyServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/config/djomy.php', 'djomy');

        $this->app->singleton('djomy-client', function () {
            $clientId = config('djomy.client_id');
            $clientSecret = config('djomy.client_secret');
            $baseUrl = config('djomy.base_url');

            if (empty($baseUrl) || empty($clientId) || empty($clientSecret)) {
                throw new ConfigurationException('DJOMY_BASE_URL, DJOMY_CLIENT_ID and DJOMY_CLIENT_SECRET must be configured');
            }

            return new DjomyClient(
                $baseUrl,
                $clientId,
                $clientSecret,
                DjomyEndpoints::AUTHENTICATE,
                (int) config('djomy.timeout', 30),
                (bool) config('djomy.auto_authenticate', false),
            );
        });

        $this->app->singleton('djomy', function () {
            return new DjomyService($this->app->make('djomy-client'));
        });

        $this->app->singleton('djomy-webhooks', function () {
            return new Webhooks(
                (string) config('djomy.client_secret'),
                (string) config('djomy.webhook_version', WebhookEvent::VERSION_V2),
            );
        });

        $this->app->alias('djomy-client', DjomyClient::class);
        $this->app->alias('djomy', DjomyService::class);
        $this->app->alias('djomy-webhooks', Webhooks::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__.'/config/djomy.php' => config_path('djomy.php'),
            ], 'config');
        }
    }
}
