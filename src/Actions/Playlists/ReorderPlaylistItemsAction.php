<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Playlists;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Exceptions\ValidationException;

/**
 * Moves a run of items to a new position. `insertBefore` is evaluated
 * against the *original* indices, so moving an item down means passing the
 * index after its target slot.
 *
 * To rewrite an entire ordering in one call, prefer
 * {@see ReplacePlaylistItemsAction}. Returns the resulting snapshot id.
 */
class ReorderPlaylistItemsAction extends BaseAction
{
    protected ?int $rangeStart = null;

    protected ?int $insertBefore = null;

    protected ?int $rangeLength = null;

    protected ?string $snapshotId = null;

    public function id(string $id): static
    {
        $this->pathParameters['id'] = $id;

        return $this;
    }

    /**
     * Zero-based index of the first item to move.
     */
    public function rangeStart(int $rangeStart): static
    {
        $this->rangeStart = $rangeStart;

        return $this;
    }

    /**
     * Zero-based index the moved run should land before.
     */
    public function insertBefore(int $insertBefore): static
    {
        $this->insertBefore = $insertBefore;

        return $this;
    }

    /**
     * How many consecutive items to move. Spotify defaults to 1.
     */
    public function rangeLength(?int $rangeLength): static
    {
        $this->rangeLength = $rangeLength;

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
        return 'PUT';
    }

    /**
     * @return array<string, mixed>
     */
    protected function body(): array
    {
        return array_filter([
            'range_start' => $this->rangeStart,
            'insert_before' => $this->insertBefore,
            'range_length' => $this->rangeLength,
            'snapshot_id' => $this->snapshotId,
        ], static fn (mixed $value): bool => $value !== null);
    }

    protected function validate(): void
    {
        if ($this->rangeStart === null || $this->insertBefore === null) {
            throw new ValidationException('Both ->rangeStart() and ->insertBefore() are required to reorder playlist items.');
        }
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
