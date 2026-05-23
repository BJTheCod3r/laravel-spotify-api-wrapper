<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Tests\Stubs;

use Illuminate\Contracts\Auth\Authenticatable;

/**
 * Minimal Authenticatable used by the test suite. Avoids depending on a
 * full Eloquent User model just to call `Auth::login()`.
 */
class TestUser implements Authenticatable
{
    public function __construct(public int|string $id = 1)
    {
    }

    public function getAuthIdentifierName(): string
    {
        return 'id';
    }

    public function getAuthIdentifier(): int|string
    {
        return $this->id;
    }

    public function getAuthPasswordName(): string
    {
        return 'password';
    }

    public function getAuthPassword(): string
    {
        return '';
    }

    public function getRememberToken(): string
    {
        return '';
    }

    public function setRememberToken($value): void
    {
    }

    public function getRememberTokenName(): string
    {
        return '';
    }
}
