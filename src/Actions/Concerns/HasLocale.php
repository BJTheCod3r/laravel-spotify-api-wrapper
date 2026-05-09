<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Concerns;

trait HasLocale
{
    protected ?string $locale = null;

    public function locale(?string $locale): static
    {
        $this->locale = $locale;

        return $this;
    }
}
