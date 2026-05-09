<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Search;

use BjTheCod3r\Spotify\Config\SpotifyConfig;
use BjTheCod3r\Spotify\Enums\SearchType;
use BjTheCod3r\Spotify\Http\SpotifyClient;
use BjTheCod3r\Spotify\Resources\Paginated;

abstract class TypedSearchAction extends SearchAction
{
    public function __construct(SpotifyClient $client, SpotifyConfig $config)
    {
        parent::__construct($client, $config);

        $this->types = [$this->type()];
    }

    abstract protected function type(): SearchType;

    /**
     * @param array<string, mixed> $payload
     */
    protected function decode(array $payload): Paginated
    {
        $key = $this->type()->pluralKey();
        $page = is_array($payload[$key] ?? null) ? $payload[$key] : [];

        return Paginated::fromArray($page, $this->type()->resourceFactory());
    }

    public function execute(): Paginated
    {
        /** @var Paginated */
        return parent::execute();
    }

    public function get(): Paginated
    {
        return $this->execute();
    }
}
