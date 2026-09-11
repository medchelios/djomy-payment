<?php

namespace Tmoh\DjomyPayment\Http;

final class ExtractErrorMessageAction
{
    private const MESSAGE_KEYS = ['message', 'detail', 'error', 'description'];

    /**
     * Extract a human-readable message from a decoded Djomy response body.
     *
     * Handles flat (`message`) and nested (`error.message`) error payloads
     * without ever passing a non-string value to an exception constructor.
     *
     * @param  array<mixed>  $body
     */
    public function execute(array $body, string $fallback = 'Unexpected Djomy API error.'): string
    {
        foreach (self::MESSAGE_KEYS as $key) {
            $message = $this->stringValue($body[$key] ?? null);

            if ($message !== null) {
                return $message;
            }
        }

        return $fallback;
    }

    private function stringValue(mixed $value): ?string
    {
        if (is_string($value) && $value !== '') {
            return $value;
        }

        if (is_array($value)) {
            foreach (self::MESSAGE_KEYS as $key) {
                $nested = $this->stringValue($value[$key] ?? null);

                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        return null;
    }
}
