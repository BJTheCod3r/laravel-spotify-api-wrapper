<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Albums;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Actions\Concerns\HasMarket;
use BjTheCod3r\Spotify\Resources\Album;

class GetAlbumAction extends BaseAction
{
    use HasMarket;

    public function id(string $id): static
    {
        $this->pathParameters['id'] = $id;

        return $this;
    }

    protected function path(): string
    {
        return '/albums/{id}';
    }

    /**
     * @return array<string, mixed>
     */
    protected function query(): array
    {
        return [
            'market' => $this->market,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function decode(array $payload): Album
    {
        return Album::fromArray($payload);
    }

    public function get(): Album
    {
        return $this->execute();
    }
}
