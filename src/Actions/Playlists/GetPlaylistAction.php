<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Playlists;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Actions\Concerns\HasMarket;
use BjTheCod3r\Spotify\Resources\Playlist;

class GetPlaylistAction extends BaseAction
{
    use HasMarket;

    protected ?string $fields = null;

    protected ?string $additionalTypes = null;

    public function id(string $id): static
    {
        $this->pathParameters['id'] = $id;

        return $this;
    }

    public function fields(?string $fields): static
    {
        $this->fields = $fields;

        return $this;
    }

    public function additionalTypes(string|array|null $types): static
    {
        if (is_array($types)) {
            $types = implode(',', $types);
        }

        $this->additionalTypes = $types;

        return $this;
    }

    protected function path(): string
    {
        return '/playlists/{id}';
    }

    /**
     * @return array<string, mixed>
     */
    protected function query(): array
    {
        return [
            'market' => $this->market,
            'fields' => $this->fields,
            'additional_types' => $this->additionalTypes,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function decode(array $payload): Playlist
    {
        return Playlist::fromArray($payload);
    }

    public function get(): Playlist
    {
        return $this->execute();
    }
}
