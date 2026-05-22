<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Contracts;

use BjTheCod3r\Spotify\Auth\UserTokenSet;

interface UserTokenRepository
{
    public function find(int|string $userId): ?UserTokenSet;

    public function store(int|string $userId, UserTokenSet $tokens): void;

    public function forget(int|string $userId): void;
}
