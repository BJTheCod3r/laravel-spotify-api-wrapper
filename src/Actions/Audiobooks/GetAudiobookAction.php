<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Audiobooks;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Actions\Concerns\HasMarket;
use BjTheCod3r\Spotify\Resources\Audiobook;

class GetAudiobookAction extends BaseAction
{
    use HasMarket;

    public function id(string $id): static
    {
        $this->pathParameters['id'] = $id;

        return $this;
    }

    protected function path(): string
    {
        return '/audiobooks/{id}';
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
    protected function decode(array $payload): Audiobook
    {
        return Audiobook::fromArray($payload);
    }

    public function get(): Audiobook
    {
        return $this->execute();
    }
}
