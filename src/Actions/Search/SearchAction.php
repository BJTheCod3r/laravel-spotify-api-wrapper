<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Search;

use BjTheCod3r\Spotify\Actions\BaseAction;
use BjTheCod3r\Spotify\Actions\Concerns\HasMarket;
use BjTheCod3r\Spotify\Actions\Concerns\HasPagination;
use BjTheCod3r\Spotify\Config\SpotifyConfig;
use BjTheCod3r\Spotify\Enums\SearchType;
use BjTheCod3r\Spotify\Exceptions\ValidationException;
use BjTheCod3r\Spotify\Http\SpotifyClient;
use BjTheCod3r\Spotify\Resources\SearchResults;

/**
 * Generic, multi-type search against `GET /v1/search`.
 *
 * @see https://developer.spotify.com/documentation/web-api/reference/search
 */
class SearchAction extends BaseAction
{
    use HasMarket;
    use HasPagination;

    protected string $query = '';

    /** @var array<int, SearchType> */
    protected array $types = [];

    protected ?string $includeExternal = null;

    public function __construct(SpotifyClient $client, protected SpotifyConfig $config)
    {
        parent::__construct($client);

        $this->market = $config->defaultMarket;
        $this->limit = $config->defaultLimit;
    }

    public function q(string $query): static
    {
        $this->query = $query;

        return $this;
    }

    /**
     * @param array<int, SearchType|string>|SearchType|string $types
     */
    public function types(array|SearchType|string $types): static
    {
        $types = is_array($types) ? $types : [$types];

        $this->types = SearchType::coerceMany($types);

        return $this;
    }

    /**
     * Spotify supports `include_external=audio` for externally hosted audio.
     */
    public function includeExternalAudio(bool $include = true): static
    {
        $this->includeExternal = $include ? 'audio' : null;

        return $this;
    }

    protected function path(): string
    {
        return '/search';
    }

    protected function validate(): void
    {
        if (trim($this->query) === '') {
            throw new ValidationException('A search query string is required.');
        }

        if ($this->types === []) {
            throw new ValidationException('At least one search type is required.');
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function query(): array
    {
        return [
            'q' => $this->query,
            'type' => implode(',', array_map(static fn (SearchType $t): string => $t->value, $this->types)),
            'market' => $this->market,
            'limit' => $this->limit,
            'offset' => $this->offset,
            'include_external' => $this->includeExternal,
        ];
    }

    /**
     * @param array<string, mixed> $payload
     */
    protected function decode(array $payload): SearchResults
    {
        return SearchResults::fromArray($payload);
    }

    public function execute(): SearchResults
    {
        /** @var SearchResults */
        return parent::execute();
    }

    public function get(): SearchResults
    {
        return $this->execute();
    }
}
