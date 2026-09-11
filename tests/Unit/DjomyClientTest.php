<?php

use GuzzleHttp\Exception\ConnectException;
use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use Tmoh\DjomyPayment\Endpoints\DjomyEndpoints;
use Tmoh\DjomyPayment\Exceptions\AuthenticationException;
use Tmoh\DjomyPayment\Exceptions\BadRequestException;
use Tmoh\DjomyPayment\Exceptions\ConnectionException;
use Tmoh\DjomyPayment\Exceptions\DjomyException;
use Tmoh\DjomyPayment\Exceptions\ForbiddenException;
use Tmoh\DjomyPayment\Exceptions\NotFoundException;
use Tmoh\DjomyPayment\Exceptions\ServerException;
use Tmoh\DjomyPayment\Exceptions\TooManyRequestsException;
use Tmoh\DjomyPayment\Exceptions\UnauthorizedException;
use Tmoh\DjomyPayment\Exceptions\UnprocessableEntityException;
use Tmoh\DjomyPayment\Tests\Support\HttpFactory;

function djomySignature(string $clientId = 'test-client-id', string $clientSecret = 'test-client-secret'): string
{
    return hash_hmac('sha256', $clientId, $clientSecret);
}

describe('DjomyClient headers', function () {
    it('signs every request with the X-API-KEY header', function () {
        $history = null;
        $client = HttpFactory::client([
            new Response(200, [], '{"id":"pl_1","status":"active"}'),
        ], $history);

        $client->get(DjomyEndpoints::PAYMENT_LINKS);

        $request = $history[0]['request'];

        expect($request->getHeaderLine('X-API-KEY'))
            ->toBe('test-client-id:'.djomySignature());
    });

    it('sends the Bearer token on authenticated requests when present', function () {
        $history = null;
        $client = HttpFactory::client([
            new Response(200, [], '{}'),
        ], $history);
        $client->setBearerToken('token-from-setter');

        $client->get(DjomyEndpoints::PAYMENT_LINKS);

        expect($history[0]['request']->getHeaderLine('Authorization'))
            ->toBe('Bearer token-from-setter');
    });
});

describe('DjomyClient authentication', function () {
    it('requests and stores the Bearer token', function () {
        $history = null;
        $client = HttpFactory::client([
            new Response(200, [], '{"data":{"accessToken":"tok_123"}}'),
        ], $history);

        $token = $client->authenticate();

        expect($token)->toBe('tok_123');
        expect($client->getBearerToken())->toBe('tok_123');
        expect($client->hasToken())->toBeTrue();
        expect($history[0]['request']->getUri()->getPath())->toBe('/v1/auth');
        expect($history[0]['request']->getHeaderLine('X-API-KEY'))->toStartWith('test-client-id:');
    });

    it('does not attach a Bearer token to the authentication request', function () {
        $history = null;
        $client = HttpFactory::client([
            new Response(200, [], '{"access_token":"abc123"}'),
        ], $history);
        $client->setBearerToken('stale-token');

        $client->authenticate();

        expect($history[0]['request']->getHeaderLine('Authorization'))->toBe('');
    });

    it('throws AuthenticationException when no token is returned', function () {
        $client = HttpFactory::client([
            new Response(200, [], '{"status":"error"}'),
        ]);

        expect(fn () => $client->authenticate())
            ->toThrow(AuthenticationException::class);
    });

    it('auto-authenticates before the first authenticated request', function () {
        $history = null;
        $client = HttpFactory::client([
            new Response(200, [], '{"token":"auto-tok"}'),
            new Response(200, [], '{"id":"pl_1"}'),
        ], $history, true);

        $client->get(DjomyEndpoints::PAYMENT_LINKS);

        expect($history)->toHaveCount(2);
        expect($history[0]['request']->getUri()->getPath())->toBe('/v1/auth');
        expect($history[1]['request']->getHeaderLine('Authorization'))->toBe('Bearer auto-tok');
    });
});

describe('DjomyClient requests', function () {
    it('performs a GET request against the configured base URL', function () {
        $history = null;
        $client = HttpFactory::client([
            new Response(200, [], '{"data":[{"id":"pl_1"}]}'),
        ], $history);

        $response = $client->get(DjomyEndpoints::PAYMENT_LINKS);

        $request = $history[0]['request'];

        expect($request->getMethod())->toBe('GET');
        expect($request->getUri()->getPath())->toBe(DjomyEndpoints::PAYMENT_LINKS);
        expect($request->getUri()->getHost())->toBe('api.djomy.com');
        expect($response)->toBe(['data' => [['id' => 'pl_1']]]);
    });

    it('sends a JSON body on POST requests', function () {
        $history = null;
        $client = HttpFactory::client([
            new Response(201, [], '{"id":"pl_new"}'),
        ], $history);

        $client->post(DjomyEndpoints::PAYMENT_LINKS, [
            'amount' => 15000,
            'currency' => 'XOF',
        ]);

        $request = $history[0]['request'];

        expect($request->getMethod())->toBe('POST');
        expect(json_decode($request->getBody()->getContents(), true))
            ->toEqual(['amount' => 15000, 'currency' => 'XOF']);
    });

    it('returns an empty array for empty response bodies', function () {
        $client = HttpFactory::client([
            new Response(204),
        ]);

        expect($client->delete(DjomyEndpoints::paymentLink('')))->toBe([]);
    });
});

describe('DjomyClient error handling', function () {
    it('throws BadRequestException for status 400', function () {
        $client = HttpFactory::client([
            new Response(400, [], '{"message":"Format de référence invalide"}'),
        ]);

        expect(fn () => $client->get(DjomyEndpoints::paymentLink('')))
            ->toThrow(BadRequestException::class, 'Format de référence invalide');
    });

    it('throws UnauthorizedException for status 401', function () {
        $client = HttpFactory::client([
            new Response(401, [], '{"detail":"Clé API manquante ou invalide"}'),
        ]);

        expect(fn () => $client->get(DjomyEndpoints::paymentLink('')))
            ->toThrow(UnauthorizedException::class, 'Clé API manquante ou invalide');
    });

    it('throws ForbiddenException for status 403', function () {
        $client = HttpFactory::client([
            new Response(403, [], '{"message":"Accès non autorisé à ce lien"}'),
        ]);

        expect(fn () => $client->get(DjomyEndpoints::paymentLink('')))
            ->toThrow(ForbiddenException::class);
    });

    it('throws NotFoundException for status 404', function () {
        $client = HttpFactory::client([
            new Response(404, [], '{"message":"Lien de paiement introuvable"}'),
        ]);

        expect(fn () => $client->get(DjomyEndpoints::paymentLink('')))
            ->toThrow(NotFoundException::class, 'Lien de paiement introuvable');
    });

    it('throws UnprocessableEntityException for status 422', function () {
        $client = HttpFactory::client([
            new Response(422, [], '{"message":"Invalid payment data"}'),
        ]);

        expect(fn () => $client->post('/v1/payments', []))
            ->toThrow(UnprocessableEntityException::class, 'Invalid payment data');
    });

    it('throws TooManyRequestsException for status 429', function () {
        $client = HttpFactory::client([
            new Response(429, [], '{"message":"Limite de taux dépassée"}'),
        ]);

        expect(fn () => $client->get(DjomyEndpoints::paymentLink('')))
            ->toThrow(TooManyRequestsException::class);
    });

    it('throws ServerException for status 500', function () {
        $client = HttpFactory::client([
            new Response(500, [], '{"message":"Erreur interne du serveur"}'),
        ]);

        expect(fn () => $client->get(DjomyEndpoints::paymentLink('')))
            ->toThrow(ServerException::class);
    });
});

describe('DjomyClient helpers', function () {
    it('exposes the base URL, client id and auth endpoint', function () {
        $client = HttpFactory::client([]);

        expect($client->getBaseUrl())->toBe('https://api.djomy.com');
        expect($client->getClientId())->toBe('test-client-id');
        expect($client->getAuthEndpoint())->toBe('/v1/auth');
        expect($client->hasToken())->toBeFalse();
    });

    it('normalizes endpoints that are missing a leading slash', function () {
        $history = null;
        $client = HttpFactory::client([
            new Response(200, [], '{}'),
        ], $history);

        $client->get(ltrim(DjomyEndpoints::PAYMENT_LINKS, '/'));

        expect($history[0]['request']->getUri()->getPath())->toBe(DjomyEndpoints::PAYMENT_LINKS);
        expect($history[0]['request']->getUri()->getHost())->toBe('api.djomy.com');
    });
});

describe('DjomyClient resilience', function () {
    it('maps connection failures to a ConnectionException', function () {
        $client = HttpFactory::client([
            new ConnectException('Could not connect', new Request('GET', DjomyEndpoints::PAYMENT_LINKS)),
        ]);

        expect(fn () => $client->get(DjomyEndpoints::PAYMENT_LINKS))
            ->toThrow(ConnectionException::class, 'Could not connect');
    });

    it('maps a non-JSON error body to an HTTP exception instead of a JsonException', function () {
        $client = HttpFactory::client([
            new Response(503, [], '<html>Service unavailable</html>'),
        ]);

        expect(fn () => $client->get(DjomyEndpoints::PAYMENT_LINKS))
            ->toThrow(ServerException::class);
    });

    it('extracts the message from a nested error payload', function () {
        $client = HttpFactory::client([
            new Response(400, [], '{"success":false,"error":{"message":"Montant invalide"}}'),
        ]);

        expect(fn () => $client->get(DjomyEndpoints::PAYMENT_LINKS))
            ->toThrow(BadRequestException::class, 'Montant invalide');
    });

    it('exposes the HTTP status code on the thrown exception', function () {
        $client = HttpFactory::client([
            new Response(404, [], '{"message":"Introuvable"}'),
        ]);

        try {
            $client->get(DjomyEndpoints::paymentLink(''));
        } catch (NotFoundException $e) {
            expect($e->getCode())->toBe(404);
        }
    });

    it('returns an empty array for scalar JSON bodies instead of failing', function () {
        $client = HttpFactory::client([
            new Response(200, [], '42'),
        ]);

        expect($client->get(DjomyEndpoints::PAYMENT_LINKS))->toBe([]);
    });

    it('throws a DjomyException when the API reports success=false with a 2xx status', function () {
        $client = HttpFactory::client([
            new Response(200, [], '{"success":false,"message":"Invalid amount"}'),
        ]);

        expect(fn () => $client->get(DjomyEndpoints::PAYMENT_LINKS))
            ->toThrow(DjomyException::class, 'Invalid amount');
    });
});
