<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Me;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Resources\User;

class GetCurrentUserProfileAction extends BaseAction
{
    protected function path(): string
    {
        return '/me';
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function decode(array $payload): User
    {
        return User::fromArray($payload);
    }

    public function get(): User
    {
        return $this->execute();
    }
}
