<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Concerns;

trait HasMarket
{
    protected ?string $market = null;

    public function market(?string $market): static
    {
        $this->market = $market;

        return $this;
    }
}
