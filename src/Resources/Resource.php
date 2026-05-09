<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Resources;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Carbon;
use JsonSerializable;

abstract class Resource implements Arrayable, JsonSerializable
{
    /**
     * @return array<string, mixed>
     */
    abstract public function toArray(): array;

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    protected static function str(mixed $value): ?string
    {
        return $value === null ? null : (string) $value;
    }

    protected static function int(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        return (int) $value;
    }

    protected static function bool(mixed $value): ?bool
    {
        return $value === null ? null : (bool) $value;
    }

    /**
     * @return array<int|string, mixed>
     */
    protected static function arr(mixed $value): array
    {
        return is_array($value) ? $value : [];
    }

    protected static function date(mixed $value): ?Carbon
    {
        if ($value === null) {
            return null;
        }

        $string = trim((string) $value);

        if ($string === '') {
            return null;
        }

        $padded = match (substr_count($string, '-')) {
            0 => $string.'-01-01',
            1 => $string.'-01',
            default => $string,
        };

        return Carbon::parse($padded);
    }

    protected static function formatDate(?Carbon $date, ?string $precision): ?string
    {
        return $date?->format(match ($precision) {
            'year' => 'Y',
            'month' => 'Y-m',
            default => 'Y-m-d',
        });

    }
}
