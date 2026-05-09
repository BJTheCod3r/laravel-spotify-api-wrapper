<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Search;

use BjTheCod3r\Spotify\Enums\SearchType;

class SearchAudiobooksAction extends TypedSearchAction
{
    protected function type(): SearchType
    {
        return SearchType::Audiobook;
    }
}
