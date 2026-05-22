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

    /*
    |--------------------------------------------------------------------------
    | User OAuth (Authorization Code + PKCE)
    |--------------------------------------------------------------------------
    |
    | Lets your users connect their personal Spotify account so the package
    | can act on their behalf — read saved tracks/playlists, top items,
    | listening history, etc. PKCE is the only flow supported.
    |
    | After publishing the migration (`php artisan vendor:publish
    | --tag=spotify-migrations`), authorized users hit the `connect` route to
    | start the OAuth dance. Tokens are stored encrypted-at-rest in the
    | `spotify_user_tokens` table and refreshed automatically.
    |
    */

    'oauth' => [
        'redirect_uri' => env('SPOTIFY_REDIRECT_URI'),

        // Scopes requested by default. Pass extras to Spotify::redirect()
        // to add per-call scopes; the union is requested.
        'default_scopes' => [
            'user-read-private',
            'user-read-email',
            'playlist-read-private',
            'playlist-read-collaborative',
            'user-library-read',
            'user-top-read',
            'user-read-recently-played',
            'user-follow-read',
        ],

        // Auth guard used to resolve the current user. `null` = default guard.
        'guard' => env('SPOTIFY_OAUTH_GUARD'),

        'routes' => [
            'enabled' => env('SPOTIFY_OAUTH_ROUTES_ENABLED', true),
            'prefix' => env('SPOTIFY_OAUTH_ROUTES_PREFIX', 'spotify'),
            'middleware' => ['web', 'auth'],
        ],

        // Session key used to stash PKCE verifier + state between the
        // redirect and callback.
        'session_key' => 'spotify.oauth',

        // Redirect targets after the callback completes.
        'after_connect' => env('SPOTIFY_OAUTH_AFTER_CONNECT', '/'),
        'after_disconnect' => env('SPOTIFY_OAUTH_AFTER_DISCONNECT', '/'),

        // Token refresh is serialised per-user to avoid two workers
        // simultaneously spending the same refresh token.
        'refresh_lock' => [
            'enabled' => true,
            'ttl' => 10,
            'wait' => 5,
        ],

        // FQCN of a UserTokenRepository implementation. `null` uses the
        // bundled Eloquent-backed default.
        'token_repository' => null,
    ],

];
