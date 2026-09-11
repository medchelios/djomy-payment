<?php

namespace Tmoh\DjomyPayment\Http;

use Psr\Http\Message\ResponseInterface;

final class DecodeResponseAction
{
    /**
     * Decode a JSON response body, tolerating empty or scalar payloads.
     *
     * @return array<mixed>
     */
    public function execute(ResponseInterface $response): array
    {
        $contents = (string) $response->getBody();

        if ($contents === '') {
            return [];
        }

        $decoded = json_decode($contents, true);

        return is_array($decoded) ? $decoded : [];
    }
}
