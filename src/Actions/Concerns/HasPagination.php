<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Actions\Concerns;

trait HasPagination
{
    protected ?int $limit = null;

    protected ?int $offset = null;

    public function limit(?int $limit): static
    {
        $this->limit = $limit;

        return $this;
    }

    public function offset(?int $offset): static
    {
        $this->offset = $offset;

        return $this;
    }
}
