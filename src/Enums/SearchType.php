<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Enums;

use BjTheCod3r\Spotify\Resources\Album;
use BjTheCod3r\Spotify\Resources\Artist;
use BjTheCod3r\Spotify\Resources\Audiobook;
use BjTheCod3r\Spotify\Resources\Episode;
use BjTheCod3r\Spotify\Resources\Resource;
use BjTheCod3r\Spotify\Resources\Show;
use BjTheCod3r\Spotify\Resources\SimplifiedPlaylist;
use BjTheCod3r\Spotify\Resources\Track;

enum SearchType: string
{
    case Album = 'album';
    case Artist = 'artist';
    case Playlist = 'playlist';
    case Track = 'track';
    case Show = 'show';
    case Episode = 'episode';
    case Audiobook = 'audiobook';

    /**
     * @param array<int, self|string> $values
     *
     * @return array<int, self>
     */
    public static function coerceMany(array $values): array
    {
        return array_map(static fn (self|string $value): self => self::coerce($value), $values);
    }

    public static function coerce(self|string $value): self
    {
        return $value instanceof self ? $value : self::from($value);
    }

    public function pluralKey(): string
    {
        return $this->value.'s';
    }

    /**
     * @return callable(array<string, mixed>): Resource
     */
    public function resourceFactory(): callable
    {
        return match ($this) {
            self::Track => Track::fromArray(...),
            self::Album => Album::fromArray(...),
            self::Artist => Artist::fromArray(...),
            self::Playlist => SimplifiedPlaylist::fromArray(...),
            self::Show => Show::fromArray(...),
            self::Episode => Episode::fromArray(...),
            self::Audiobook => Audiobook::fromArray(...),
        };
    }
}
