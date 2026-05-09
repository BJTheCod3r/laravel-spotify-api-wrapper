<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Search;

use BjTheCod3r\Spotify\Resources\SearchResults;

class SearchAction extends AbstractSearchAction
{
    /**
     * @param array<string, mixed> $payload
     */
    protected function decode(array $payload): SearchResults
    {
        return SearchResults::fromArray($payload);
    }

    public function get(): SearchResults
    {
        return $this->execute();
    }
}
