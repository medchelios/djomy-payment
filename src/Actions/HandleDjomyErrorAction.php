<?php

namespace Tmoh\DjomyPayment\Actions;

use GuzzleHttp\Exception\RequestException;
use Tmoh\DjomyPayment\Exceptions\BadRequestException;
use Tmoh\DjomyPayment\Exceptions\ConnectionException;
use Tmoh\DjomyPayment\Exceptions\DjomyException;
use Tmoh\DjomyPayment\Exceptions\ForbiddenException;
use Tmoh\DjomyPayment\Exceptions\NotFoundException;
use Tmoh\DjomyPayment\Exceptions\ServerException;
use Tmoh\DjomyPayment\Exceptions\TooManyRequestsException;
use Tmoh\DjomyPayment\Exceptions\UnauthorizedException;
use Tmoh\DjomyPayment\Exceptions\UnprocessableEntityException;
use Tmoh\DjomyPayment\Http\ExtractErrorMessageAction;

final class HandleDjomyErrorAction
{
    private readonly ExtractErrorMessageAction $extractMessage;

    public function __construct()
    {
        $this->extractMessage = new ExtractErrorMessageAction;
    }

    /**
     * @throws ConnectionException
     * @throws ForbiddenException
     * @throws TooManyRequestsException
     * @throws DjomyException
     * @throws UnauthorizedException
     * @throws UnprocessableEntityException
     * @throws BadRequestException
     * @throws ServerException
     * @throws NotFoundException
     */
    public function execute(\Throwable $exception): never
    {
        if (! $exception instanceof RequestException || $exception->getResponse() === null) {
            throw new ConnectionException(
                $exception->getMessage() !== '' ? $exception->getMessage() : 'Unable to reach the Djomy API.',
                0,
                $exception,
            );
        }

        $response = $exception->getResponse();
        $status = $response->getStatusCode();

        $decoded = json_decode((string) $response->getBody(), true);
        $decoded = is_array($decoded) ? $decoded : [];

        $message = $this->extractMessage->execute($decoded, $exception->getMessage());

        throw match (true) {
            $status === 400 => new BadRequestException($message, $status, $exception),
            $status === 401 => new UnauthorizedException($message, $status, $exception),
            $status === 403 => new ForbiddenException($message, $status, $exception),
            $status === 404 => new NotFoundException($message, $status, $exception),
            $status === 422 => new UnprocessableEntityException($message, $status, $exception),
            $status === 429 => new TooManyRequestsException($message, $status, $exception),
            $status >= 500 => new ServerException($message, $status, $exception),
            default => new DjomyException($message, $status, $exception),
        };
    }
}
