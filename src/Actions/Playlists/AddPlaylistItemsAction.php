<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Playlists;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Actions\Concerns\HasPlaylistItemUris;

/**
 * Appends items to a playlist, or inserts them at {@see position()}.
 * Returns the resulting snapshot id.
 */
class AddPlaylistItemsAction extends BaseAction
{
    use HasPlaylistItemUris;

    protected ?int $position = null;

    public function id(string $id): static
    {
        $this->pathParameters['id'] = $id;

        return $this;
    }

    /**
     * Zero-based insert position. Null appends to the end.
     */
    public function position(?int $position): static
    {
        $this->position = $position;

        return $this;
    }

    protected function path(): string
    {
        return '/playlists/{id}/tracks';
    }

    protected function method(): string
    {
        return 'POST';
    }

    /**
     * @return array<string, mixed>
     */
    protected function body(): array
    {
        $body = ['uris' => $this->uris];

        if ($this->position !== null) {
            $body['position'] = $this->position;
        }

        return $body;
    }

    protected function validate(): void
    {
        $this->guardUris();
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function decode(array $payload): string
    {
        return (string) ($payload['snapshot_id'] ?? '');
    }

    public function get(): string
    {
        return $this->execute();
    }
}
