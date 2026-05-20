<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Artists;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Resources\Artist;

class GetArtistAction extends BaseAction
{
    public function id(string $id): static
    {
        $this->pathParameters['id'] = $id;

        return $this;
    }

    protected function path(): string
    {
        return '/artists/{id}';
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function decode(array $payload): Artist
    {
        return Artist::fromArray($payload);
    }

    public function get(): Artist
    {
        return $this->execute();
    }
}
