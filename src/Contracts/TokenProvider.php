<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Contracts;

use BjTheCod3r\Spotify\Auth\Token;

interface TokenProvider
{
    /**
     * Return a valid access token, requesting a new one when the cached
     * token is missing or expired.
     */
    public function token(): Token;

    /**
     * Force-discard any cached token so the next call refetches.
     */
    public function forget(): void;
}
