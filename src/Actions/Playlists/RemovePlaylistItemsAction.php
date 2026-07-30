<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Playlists;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Actions\Concerns\HasPlaylistItemUris;

/**
 * Removes every occurrence of the given items from a playlist. Pass the
 * playlist's snapshot id to have Spotify reject the call if the playlist
 * changed since you read it. Returns the resulting snapshot id.
 */
class RemovePlaylistItemsAction extends BaseAction
{
    use HasPlaylistItemUris;

    protected ?string $snapshotId = null;

    public function id(string $id): static
    {
        $this->pathParameters['id'] = $id;

        return $this;
    }

    public function snapshotId(?string $snapshotId): static
    {
        $this->snapshotId = $snapshotId;

        return $this;
    }

    protected function path(): string
    {
        return '/playlists/{id}/tracks';
    }

    protected function method(): string
    {
        return 'DELETE';
    }

    /**
     * @return array<string, mixed>
     */
    protected function body(): array
    {
        $body = [
            'tracks' => array_map(
                static fn (string $uri): array => ['uri' => $uri],
                $this->uris,
            ),
        ];

        if ($this->snapshotId !== null) {
            $body['snapshot_id'] = $this->snapshotId;
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
