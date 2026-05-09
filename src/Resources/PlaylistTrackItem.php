<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Resources;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

final class PlaylistTrackItem extends Resource
{
    /**
     * @param array<string, mixed> $track
     */
    public function __construct(
        public readonly ?Carbon $addedAt,
        public readonly ?User $addedBy,
        public readonly bool $isLocal,
        public readonly ?string $primaryColor,
        public readonly Track|Episode|array|null $track,
    ) {
    }

    /**
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        $rawAddedBy = $data['added_by'] ?? null;
        $rawTrack = $data['track'] ?? null;

        return new self(
            addedAt: self::date($data['added_at'] ?? null),
            addedBy: is_array($rawAddedBy) ? User::fromArray($rawAddedBy) : null,
            isLocal: (bool) ($data['is_local'] ?? false),
            primaryColor: self::str($data['primary_color'] ?? null),
            track: self::track($rawTrack),
        );
    }

    /**
     * @return Collection<int, self>
     */
    public static function collection(mixed $data): Collection
    {
        if (! is_array($data)) {
            return collect();
        }

        return collect($data)
            ->map(static fn (mixed $item): self => self::fromArray(is_array($item) ? $item : []))
            ->values();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'added_at' => $this->addedAt?->toISOString(),
            'added_by' => $this->addedBy?->toArray(),
            'is_local' => $this->isLocal,
            'primary_color' => $this->primaryColor,
            'track' => $this->track instanceof Resource ? $this->track->toArray() : $this->track,
        ];
    }

    private static function track(mixed $track): Track|Episode|array|null
    {
        if (! is_array($track)) {
            return null;
        }

        return match ($track['type'] ?? null) {
            'track' => Track::fromArray($track),
            'episode' => Episode::fromArray($track),
            default => $track,
        };
    }
}
