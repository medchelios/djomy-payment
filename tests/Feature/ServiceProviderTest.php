<?php

use Tmoh\DjomyPayment\DjomyClient;
use Tmoh\DjomyPayment\DjomyService;
use Tmoh\DjomyPayment\Exceptions\ConfigurationException;
use Tmoh\DjomyPayment\Facades\Djomy;
use Tmoh\DjomyPayment\Facades\DjomyWebhook;
use Tmoh\DjomyPayment\Webhooks;

it('resolves the DjomyService from the container', function () {
    $this->app->forgetInstance('djomy');

    expect($this->app->make('djomy'))->toBeInstanceOf(DjomyService::class);
    expect($this->app[DjomyService::class])->toBeInstanceOf(DjomyService::class);
});

it('resolves the DjomyClient from the container with the configured credentials', function () {
    $this->app->forgetInstance('djomy-client');

    $client = $this->app->make('djomy-client');

    expect($client)->toBeInstanceOf(DjomyClient::class);
    expect($client->getBaseUrl())->toBe('https://api.djomy.com');
    expect($client->getClientId())->toBe('test-client-id');
});

it('exposes the service through the facade', function () {
    $this->app->forgetInstance('djomy');

    expect(Djomy::getClient())->toBeInstanceOf(DjomyClient::class);
});

it('throws a ConfigurationException when credentials are missing', function () {
    config(['djomy.client_id' => null, 'djomy.client_secret' => null]);
    $this->app->forgetInstance('djomy-client');
    $this->app->forgetInstance('djomy');

    expect(fn () => $this->app->make('djomy-client'))
        ->toThrow(ConfigurationException::class);
});

it('shares the same client instance between the service and the container', function () {
    $this->app->forgetInstance('djomy-client');
    $this->app->forgetInstance('djomy');

    $service = resolve('djomy');

    expect($service->getClient())->toBe($this->app->make('djomy-client'));
});

it('resolves the webhook verifier from the container', function () {
    $this->app->forgetInstance('djomy-webhooks');

    $webhooks = $this->app->make('djomy-webhooks');

    expect($webhooks)->toBeInstanceOf(Webhooks::class);
    expect($webhooks->version())->toBe('v2');
    expect($this->app[Webhooks::class])->toBeInstanceOf(Webhooks::class);
});

it('exposes the webhook verifier through the facade', function () {
    $this->app->forgetInstance('djomy-webhooks');

    expect(DjomyWebhook::version())->toBe('v2');
});
