<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Users;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Resources\User;

class GetUserAction extends BaseAction
{
    public function id(string $id): static
    {
        $this->pathParameters['user_id'] = $id;

        return $this;
    }

    protected function path(): string
    {
        return '/users/{user_id}';
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
