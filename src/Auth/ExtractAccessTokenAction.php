<?php

namespace Tmoh\DjomyPayment\Auth;

use Tmoh\DjomyPayment\Exceptions\AuthenticationException;

final class ExtractAccessTokenAction
{
    /**
     * @param  array<mixed>  $body
     */
    public function execute(array $body): string
    {
        $candidates = [
            $body['access_token'] ?? null,
            $body['token'] ?? null,
            $body['data']['access_token'] ?? null,
            $body['data']['accessToken'] ?? null,
            $body['data']['token'] ?? null,
            $body['authorization']['token'] ?? null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '') {
                return $candidate;
            }
        }

        throw new AuthenticationException('Unable to extract the access token from the authentication response.');
    }
}
