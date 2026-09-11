<?php

namespace Tmoh\DjomyPayment\Http;

final class BuildRequestOptionsAction
{
    /**
     * @param  array<string, string>  $headers
     * @param  array<mixed>  $data
     * @param  array<mixed>  $query
     * @return array<string, mixed>
     */
    public function execute(array $headers, array $data, array $query, bool $emptyJsonBody): array
    {
        $options = ['headers' => $headers];

        if ($query !== []) {
            $options['query'] = $query;
        }

        if ($data !== []) {
            $options['json'] = $data;
        } elseif ($emptyJsonBody) {
            $options['body'] = '{}';
            $options['headers']['Content-Type'] = 'application/json';
        }

        return $options;
    }
}
