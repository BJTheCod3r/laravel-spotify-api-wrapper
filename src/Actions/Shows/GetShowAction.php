<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Shows;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Actions\Concerns\HasMarket;
use BjTheCod3r\Spotify\Resources\Show;

class GetShowAction extends BaseAction
{
    use HasMarket;

    public function id(string $id): static
    {
        $this->pathParameters['id'] = $id;

        return $this;
    }

    protected function path(): string
    {
        return '/shows/{id}';
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
    protected function decode(array $payload): Show
    {
        return Show::fromArray($payload);
    }

    public function get(): Show
    {
        return $this->execute();
    }
}
