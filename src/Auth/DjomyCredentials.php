<?php

namespace Tmoh\DjomyPayment\Auth;

final class DjomyCredentials
{
    public function __construct(
        public readonly string $clientId,
        public readonly string $clientSecret,
    ) {}
}
