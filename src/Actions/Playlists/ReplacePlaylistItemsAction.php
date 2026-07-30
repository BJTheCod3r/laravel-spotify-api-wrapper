<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Playlists;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Actions\Concerns\HasPlaylistItemUris;

/**
 * Overwrites a playlist's items with the given list, sending the whole
 * ordering in one call. This is how you persist a reordered or edited
 * tracklist without diffing it. An empty list clears the playlist. Returns
 * the snapshot id.
 */
class ReplacePlaylistItemsAction extends BaseAction
{
    use HasPlaylistItemUris;

    public function id(string $id): static
    {
        $this->pathParameters['id'] = $id;

        return $this;
    }

    protected function path(): string
    {
        return '/playlists/{id}/tracks';
    }

    protected function method(): string
    {
        return 'PUT';
    }

    /**
     * @return array<string, mixed>
     */
    protected function body(): array
    {
        return ['uris' => $this->uris];
    }

    protected function validate(): void
    {
        $this->guardUris(allowEmpty: true);
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
