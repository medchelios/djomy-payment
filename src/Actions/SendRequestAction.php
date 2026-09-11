<?php

namespace Tmoh\DjomyPayment\Actions;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Psr\Http\Message\ResponseInterface;

final class SendRequestAction
{
    public function __construct(private readonly Client $client) {}

    /**
     * @throws GuzzleException
     */
    public function execute(string $method, string $url, array $options): ResponseInterface
    {
        return $this->client->request($method, $url, $options);
    }
}
