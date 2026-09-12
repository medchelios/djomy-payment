<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | Base URL for the Djomy Payment Platform API.
    |
    */

    'base_url' => env('DJOMY_BASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Credentials
    |--------------------------------------------------------------------------
    |
    | Credentials (clientId / clientSecret) provided through the merchant area.
    | They are used to generate the HMAC signature for the X-API-KEY header and
    | request a Bearer access token.
    |
    */

    'client_id' => env('DJOMY_CLIENT_ID'),

    'client_secret' => env('DJOMY_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | Authentication endpoint
    |--------------------------------------------------------------------------
    |
    | Endpoint used to retrieve the Bearer access token.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    */

    'timeout' => env('DJOMY_TIMEOUT', 30),

    /*
    |--------------------------------------------------------------------------
    | Auto authenticate
    |--------------------------------------------------------------------------
    |
    | When enabled, the client automatically requests an access token before
    | the first authenticated request if no token has been provided.
    |
    */

    'auto_authenticate' => env('DJOMY_AUTO_AUTHENTICATE', false),

    /*
    |--------------------------------------------------------------------------
    | Webhooks
    |--------------------------------------------------------------------------
    |
    | Endpoint hosted by the application and registered in the Djomy developer
    | area to receive transaction status notifications. The payload version
    | (v1 or v2) is carried by the Djomy webhook configuration.
    |
    */

    'webhook_url' => env('DJOMY_WEBHOOK_URL'),

    'webhook_version' => env('DJOMY_WEBHOOK_VERSION', 'v2'),
];
