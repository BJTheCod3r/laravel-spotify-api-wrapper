<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Tracks;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Actions\Concerns\HasMarket;
use BjTheCod3r\Spotify\Resources\Track;

class GetTrackAction extends BaseAction
{
    use HasMarket;

    public function id(string $id): static
    {
        $this->pathParameters['id'] = $id;

        return $this;
    }

    protected function path(): string
    {
        return '/tracks/{id}';
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
    protected function decode(array $payload): Track
    {
        return Track::fromArray($payload);
    }

    public function get(): Track
    {
        return $this->execute();
    }
}
