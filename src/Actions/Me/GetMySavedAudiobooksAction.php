<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Me;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Actions\Concerns\HasPagination;
use BjTheCod3r\Spotify\Resources\Audiobook;
use BjTheCod3r\Spotify\Resources\Paginated;

class GetMySavedAudiobooksAction extends BaseAction
{
    use HasPagination;

    protected function path(): string
    {
        return '/me/audiobooks';
    }

    /**
     * @return array<string, mixed>
     */
    protected function query(): array
    {
        return [
            'limit' => $this->limit,
            'offset' => $this->offset,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return Paginated<Audiobook>
     */
    protected function decode(array $payload): Paginated
    {
        return Paginated::fromArray(
            $payload,
            static fn (array $item): Audiobook => Audiobook::fromArray($item),
        );
    }

    /**
     * @return Paginated<Audiobook>
     */
    public function get(): Paginated
    {
        return $this->execute();
    }
}
