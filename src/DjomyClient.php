<?php

namespace Tmoh\DjomyPayment;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Tmoh\DjomyPayment\Actions\HandleDjomyErrorAction;
use Tmoh\DjomyPayment\Actions\SendRequestAction;
use Tmoh\DjomyPayment\Auth\DjomyCredentials;
use Tmoh\DjomyPayment\Auth\ExtractAccessTokenAction;
use Tmoh\DjomyPayment\Endpoints\DjomyEndpoints;
use Tmoh\DjomyPayment\Exceptions\DjomyException;
use Tmoh\DjomyPayment\Http\BuildRequestOptionsAction;
use Tmoh\DjomyPayment\Http\DecodeResponseAction;
use Tmoh\DjomyPayment\Http\ExtractErrorMessageAction;

/**
 * HTTP transport for the Djomy Payment Platform API.
 *
 * - Signs each request with the X-API-KEY header (clientId:hmac-signature).
 * - Retrieves a Bearer access token through the authentication endpoint.
 *
 * Framework-agnostic: works with Laravel, Symfony, or standalone PHP.
 */
final class DjomyClient
{
    private const CONTENT_TYPE = 'application/json';

    private Client $http;

    private string $baseUrl;

    private readonly DjomyCredentials $credentials;

    private readonly SendRequestAction $sendRequest;

    private readonly HandleDjomyErrorAction $handleErrorAction;

    private readonly ExtractAccessTokenAction $extractAccessToken;

    private readonly BuildRequestOptionsAction $buildRequestOptions;

    private readonly DecodeResponseAction $decodeResponse;

    private readonly ExtractErrorMessageAction $extractErrorMessage;

    private string $authEndpoint;

    private int $timeout;

    private bool $autoAuthenticate;

    private ?string $bearerToken = null;

    public function __construct(
        string $baseUrl,
        string $clientId,
        string $clientSecret,
        string $authEndpoint = DjomyEndpoints::AUTHENTICATE,
        int $timeout = 30,
        bool $autoAuthenticate = false,
        ?Client $http = null,
    ) {
        $this->baseUrl = rtrim($baseUrl, '/');
        $this->credentials = new DjomyCredentials($clientId, $clientSecret);
        $this->authEndpoint = '/'.ltrim($authEndpoint, '/');
        $this->timeout = $timeout;
        $this->autoAuthenticate = $autoAuthenticate;
        $this->http = $http ?? new Client(['timeout' => $timeout]);
        $this->sendRequest = new SendRequestAction($this->http);
        $this->handleErrorAction = new HandleDjomyErrorAction;
        $this->extractAccessToken = new ExtractAccessTokenAction;
        $this->buildRequestOptions = new BuildRequestOptionsAction;
        $this->decodeResponse = new DecodeResponseAction;
        $this->extractErrorMessage = new ExtractErrorMessageAction;
    }

    public function getBaseUrl(): string
    {
        return $this->baseUrl;
    }

    public function getClientId(): string
    {
        return $this->credentials->clientId;
    }

    public function getAuthEndpoint(): string
    {
        return $this->authEndpoint;
    }

    public function getTimeout(): int
    {
        return $this->timeout;
    }

    public function shouldAutoAuthenticate(): bool
    {
        return $this->autoAuthenticate;
    }

    public function getBearerToken(): ?string
    {
        return $this->bearerToken;
    }

    public function hasToken(): bool
    {
        return $this->bearerToken !== null;
    }

    public function setBearerToken(string $token): self
    {
        $this->bearerToken = $token;

        return $this;
    }

    /**
     * X-API-KEY header value: `<clientId>:<hmac-signature>`.
     */
    public function apiKeyHeaderValue(): string
    {
        return Signature::apiKeyHeaderValue($this->credentials->clientId, $this->credentials->clientSecret);
    }

    /**
     * Requests and stores a Bearer access token.
     */
    public function authenticate(): string
    {
        $response = $this->send('POST', $this->authEndpoint, [], [], false, true);

        $token = $this->extractAccessToken->execute($response);

        $this->bearerToken = $token;

        return $token;
    }

    public function get(string $endpoint, array $query = []): array
    {
        return $this->send('GET', $endpoint, [], $query, true);
    }

    public function post(string $endpoint, array $data = []): array
    {
        return $this->send('POST', $endpoint, $data, [], true);
    }

    public function delete(string $endpoint): array
    {
        return $this->send('DELETE', $endpoint, [], [], true);
    }

    /**
     * @return array<mixed>
     */
    private function send(string $method, string $endpoint, array $data, array $query, bool $withAuth, bool $emptyJsonBody = false): array
    {
        if ($withAuth && $this->autoAuthenticate && $this->bearerToken === null) {
            $this->authenticate();
        }

        $options = $this->buildRequestOptions->execute(
            $this->headers($withAuth),
            $data,
            $query,
            $emptyJsonBody,
        );

        try {
            $response = $this->sendRequest->execute($method, $this->endpointUrl($endpoint), $options);

            $body = $this->decodeResponse->execute($response);

            $this->guardAgainstApiFailure($body);

            return $body;
        } catch (GuzzleException $e) {
            $this->handleErrorAction->execute($e);
        }
    }

    private function endpointUrl(string $endpoint): string
    {
        return $this->baseUrl.'/'.ltrim($endpoint, '/');
    }

    /**
     * @param  array<mixed>  $body
     *
     * @throws DjomyException
     */
    private function guardAgainstApiFailure(array $body): void
    {
        if (($body['success'] ?? null) !== false) {
            return;
        }

        throw new DjomyException(
            $this->extractErrorMessage->execute($body, 'The Djomy API reported a failure.'),
        );
    }

    /**
     * @return array<string, string>
     */
    private function headers(bool $withAuth): array
    {
        $headers = [
            'X-API-KEY' => $this->apiKeyHeaderValue(),
            'Accept' => self::CONTENT_TYPE,
        ];

        if ($withAuth && $this->bearerToken !== null) {
            $headers['Authorization'] = 'Bearer '.$this->bearerToken;
        }

        return $headers;
    }
}
