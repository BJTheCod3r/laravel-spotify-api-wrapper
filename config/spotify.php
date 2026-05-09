<?php

declare(strict_types=1);

return [

    /*
    |--------------------------------------------------------------------------
    | Spotify API Credentials
    |--------------------------------------------------------------------------
    |
    | Register an application at https://developer.spotify.com/dashboard to
    | obtain a client ID and client secret. The Client Credentials grant
    | exchanges these for an access token and supports every endpoint that
    | does not require user context (Search, Browse, Albums, Artists, ...).
    |
    */

    'client_id' => env('SPOTIFY_CLIENT_ID'),

    'client_secret' => env('SPOTIFY_CLIENT_SECRET'),

    /*
    |--------------------------------------------------------------------------
    | API Endpoints
    |--------------------------------------------------------------------------
    */

    'endpoints' => [
        'accounts' => env('SPOTIFY_ACCOUNTS_URL', 'https://accounts.spotify.com'),
        'api' => env('SPOTIFY_API_URL', 'https://api.spotify.com/v1'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Defaults
    |--------------------------------------------------------------------------
    |
    | Default values applied to every request unless overridden on the
    | individual action via the fluent builder (e.g. ->market('US')).
    |
    */

    'defaults' => [
        'market' => env('SPOTIFY_MARKET'),
        'locale' => env('SPOTIFY_LOCALE'),
        'limit' => 20,
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Cache
    |--------------------------------------------------------------------------
    |
    | Access tokens issued via Client Credentials are valid for ~1 hour. We
    | cache them so subsequent requests within that window reuse the same
    | token. The TTL is offset by `ttl_buffer` seconds to avoid race
    | conditions around expiry.
    |
    */

    'cache' => [
        'store' => env('SPOTIFY_CACHE_STORE'),
        'key' => 'spotify.client_credentials.token',
        'ttl_buffer' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | HTTP Client
    |--------------------------------------------------------------------------
    */

    'http' => [
        'timeout' => env('SPOTIFY_HTTP_TIMEOUT', 10),
        'retry' => [
            'times' => env('SPOTIFY_HTTP_RETRY_TIMES', 1),
            'sleep' => env('SPOTIFY_HTTP_RETRY_SLEEP', 200),
        ],
    ],

];
