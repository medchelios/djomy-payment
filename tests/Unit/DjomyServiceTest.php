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

describe('payments', function () {
    it('initiates a portal payment with the gateway endpoint', function () {
        $history = null;
        $service = new DjomyService(HttpFactory::client([
            new Response(201, [], '{"redirectUrl":"https:\/\/sandbox.djomy.africa\/pay\/txn_1"}'),
        ], $history));

        $payload = [
            'amount' => 15000,
            'countryCode' => 'GN',
            'payerNumber' => '00224623707722',
            'allowedPaymentMethods' => ['OM', 'CARD'],
            'returnUrl' => 'https://example.com/payments/success',
        ];

        $response = $service->initiatePortalPayment($payload);

        $request = $history[0]['request'];

        expect($request->getMethod())->toBe('POST');
        expect($request->getUri()->getPath())->toBe('/v1/payments/gateway');
        expect(json_decode((string) $request->getBody(), true))->toBe($payload);
        expect($response['redirectUrl'])->toBe('https://sandbox.djomy.africa/pay/txn_1');
    });

    it('lists payments with the provided filters', function () {
        $history = null;
        $service = new DjomyService(HttpFactory::client([
            new Response(200, [], '{"data":[]}'),
        ], $history));

        $response = $service->getAllPayments([
            'statuses' => 'SUCCESS,PENDING',
            'startDate' => '2024-01-01',
        ]);

        $request = $history[0]['request'];

        expect($request->getMethod())->toBe('GET');
        expect($request->getUri()->getPath())->toBe('/v1/payments');
        expect($request->getUri()->getQuery())->toContain('statuses=SUCCESS%2CPENDING');
        expect($request->getUri()->getQuery())->toContain('startDate=2024-01-01');
        expect($response)->toBe(['data' => []]);
    });
});

describe('payouts', function () {
    it('retrieves a payout item of an order', function () {
        $history = null;
        $service = new DjomyService(HttpFactory::client([
            new Response(200, [], '{"success":true,"data":{"id":"payout_1","status":"SUCCESS"}}'),
        ], $history));

        $orderId = '9f1c2d3e-4a5b-6c7d-8e9f-0a1b2c3d4e5f';
        $payoutId = '9f1c2d3e-4a5b-6c7d-8e9f-0a1b2c3d4e5f';

        $response = $service->getPayout($orderId, $payoutId);

        $request = $history[0]['request'];

        expect($request->getMethod())->toBe('GET');
        expect($request->getUri()->getPath())
            ->toBe("/v1/payout-orders/{$orderId}/payout-items/{$payoutId}");
        expect($response['data']['status'])->toBe('SUCCESS');
    });

    it('URL-encodes the order and payout identifiers', function () {
        $history = null;
        $service = new DjomyService(HttpFactory::client([
            new Response(200, [], '{}'),
        ], $history));

        $service->getPayout('order with space', 'payout with space');

        expect($history[0]['request']->getUri()->getPath())
            ->toBe('/v1/payout-orders/order%20with%20space/payout-items/payout%20with%20space');
    });

    it('re-submits a failed payout to the provider', function () {
        $history = null;
        $service = new DjomyService(HttpFactory::client([
            new Response(200, [], '{"success":true,"message":"Payout sent for retry"}'),
        ], $history));

        $orderId = '101c2d3e-4a5b-6c7d-8e9f-0a1b2c3d4e5f';
        $payoutId = '9f1c2d3e-4a5b-6c7d-8e9f-0a1b2c3d4e5f';

        $response = $service->retryPayout($orderId, $payoutId);

        $request = $history[0]['request'];

        expect($request->getMethod())->toBe('POST');
        expect($request->getUri()->getPath())
            ->toBe("/v1/payout-orders/{$orderId}/payout-items/{$payoutId}/retry");
        expect($response['success'])->toBeTrue();
    });

    it('URL-encodes the identifiers when retrying a payout', function () {
        $history = null;
        $service = new DjomyService(HttpFactory::client([
            new Response(200, [], '{}'),
        ], $history));

        $service->retryPayout('order with space', 'payout with space');

        expect($history[0]['request']->getUri()->getPath())
            ->toBe('/v1/payout-orders/order%20with%20space/payout-items/payout%20with%20space/retry');
    });

    it('lists the paginated payouts of an order', function () {
        $history = null;
        $service = new DjomyService(HttpFactory::client([
            new Response(200, [], '{"data":[]}'),
        ], $history));

        $response = $service->getPayouts('D4E5F6', [
            'paginationRequest' => [
                'page' => 0,
                'size' => 20,
            ],
        ]);

        $request = $history[0]['request'];

        expect($request->getMethod())->toBe('GET');
        expect($request->getUri()->getPath())->toBe('/v1/payout-orders/D4E5F6/payout-items');
        expect($request->getUri()->getQuery())->toContain('paginationRequest');
        expect($response)->toBe(['data' => []]);
    });

    it('URL-encodes the order identifier when listing payouts', function () {
        $history = null;
        $service = new DjomyService(HttpFactory::client([
            new Response(200, [], '{}'),
        ], $history));

        $service->getPayouts('order with space');

        expect($history[0]['request']->getUri()->getPath())
            ->toBe('/v1/payout-orders/order%20with%20space/payout-items');
    });
});

describe('payout orders', function () {
    it('creates a payout order', function () {
        $history = null;
        $service = new DjomyService(HttpFactory::client([
            new Response(201, [], '{"success":true,"data":{"id":"D4E5F6"}}'),
        ], $history));

        $payload = [
            'description' => 'Paiement des commissions - août 2026',
            'items' => [
                [
                    'message' => 'Commission de vente',
                    'amount' => 150000,
                    'beneficiary' => ['name' => 'Mamadou Diallo'],
                    'destination' => [
                        'type' => 'WALLET',
                        'countryCode' => 'GN',
                        'currencyCode' => 'GNF',
                        'account' => [
                            'accountNumber' => '622000000',
                            'providerCode' => 'OM',
                        ],
                    ],
                ],
            ],
        ];

        $response = $service->initiatePayout($payload);

        $request = $history[0]['request'];

        expect($request->getMethod())->toBe('POST');
        expect($request->getUri()->getPath())->toBe('/v1/payout-orders');
        expect($request->getUri()->getQuery())->toBe('');
        expect(json_decode((string) $request->getBody(), true))->toBe($payload);
        expect($response['data']['id'])->toBe('D4E5F6');
    });

    it('simulates a payout order with dryRun', function () {
        $history = null;
        $service = new DjomyService(HttpFactory::client([
            new Response(200, [], '{"success":true}'),
        ], $history));

        $service->initiatePayout(['description' => 'Simulation', 'items' => []], true);

        $request = $history[0]['request'];

        expect($request->getMethod())->toBe('POST');
        expect($request->getUri()->getQuery())->toContain('dryRun=1');
    });

    it('retrieves a payout order by identifier or reference', function () {
        $history = null;
        $service = new DjomyService(HttpFactory::client([
            new Response(200, [], '{"success":true,"data":{"id":"D4E5F6"}}'),
        ], $history));

        $response = $service->getPayoutOrder('D4E5F6');

        $request = $history[0]['request'];

        expect($request->getMethod())->toBe('GET');
        expect($request->getUri()->getPath())->toBe('/v1/payout-orders/D4E5F6');
        expect($response['data']['id'])->toBe('D4E5F6');
    });

    it('URL-encodes the order identifier when retrieving a payout order', function () {
        $history = null;
        $service = new DjomyService(HttpFactory::client([
            new Response(200, [], '{}'),
        ], $history));

        $service->getPayoutOrder('order with space');

        expect($history[0]['request']->getUri()->getPath())
            ->toBe('/v1/payout-orders/order%20with%20space');
    });

    it('lists the paginated payout orders of the merchant', function () {
        $history = null;
        $service = new DjomyService(HttpFactory::client([
            new Response(200, [], '{"data":[]}'),
        ], $history));

        $response = $service->getPayoutOrders([
            'paginationRequest' => [
                'page' => 0,
                'size' => 20,
            ],
        ]);

        $request = $history[0]['request'];

        expect($request->getMethod())->toBe('GET');
        expect($request->getUri()->getPath())->toBe('/v1/payout-orders');
        expect($request->getUri()->getQuery())->toContain('paginationRequest');
        expect($response)->toBe(['data' => []]);
    });
});
