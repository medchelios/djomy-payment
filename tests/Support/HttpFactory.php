<?php

namespace Tmoh\DjomyPayment\Tests\Support;

use GuzzleHttp\Client;
use GuzzleHttp\Handler\MockHandler;
use GuzzleHttp\HandlerStack;
use GuzzleHttp\Middleware;
use GuzzleHttp\Psr7\Response;
use Tmoh\DjomyPayment\DjomyClient;

final class HttpFactory
{
    /**
     * Create a DjomyClient backed by a Guzzle MockHandler.
     *
     * @param  array<Response>  $responses
     */
    public static function client(array $responses, ?array &$history = null, bool $autoAuthenticate = false): DjomyClient
    {
        $mock = new MockHandler($responses);
        $stack = HandlerStack::create($mock);
        $container = new \ArrayObject;
        $stack->push(Middleware::history($container));

        $history = $container;

        return new DjomyClient(
            'https://api.djomy.com',
            'test-client-id',
            'test-client-secret',
            '/v1/auth',
            30,
            $autoAuthenticate,
            new Client(['handler' => $stack]),
        );
    }
}
