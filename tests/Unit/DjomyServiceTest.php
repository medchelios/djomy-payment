<?php

use GuzzleHttp\Psr7\Response;
use Tmoh\DjomyPayment\DjomyService;
use Tmoh\DjomyPayment\Tests\Support\HttpFactory;

it('instantiates with a client', function () {
    $client = HttpFactory::client([new Response(200, [], '{}')]);
    $service = new DjomyService($client);

    expect($service)->toBeInstanceOf(DjomyService::class);
    expect($service->getClient())->toBe($client);
});

describe('payment links', function () {
    it('retrieves a payment link by its reference', function () {
        $history = null;
        $service = new DjomyService(HttpFactory::client([
            new Response(200, [], '{"reference":"PL-2024-0001","status":"active","amount":5000}'),
        ], $history));

        $response = $service->getPaymentLink('PL-2024-0001');

        $request = $history[0]['request'];

        expect($request->getMethod())->toBe('GET');
        expect($request->getUri()->getPath())->toBe('/v1/links/PL-2024-0001');
        expect($response['status'])->toBe('active');
    });

    it('URL-encodes the payment link reference', function () {
        $history = null;
        $service = new DjomyService(HttpFactory::client([
            new Response(200, [], '{}'),
        ], $history));

        $service->getPaymentLink('ref with space');

        expect($history[0]['request']->getUri()->getPath())
            ->toBe('/v1/links/ref%20with%20space');
    });
});
