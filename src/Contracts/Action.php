<?php

declare(strict_types=1);

namespace BjTheCod3r\Spotify\Contracts;

interface Action
{
    /**
     * Execute the action and return the decoded response.
     *
     * Default actions return `array<string, mixed>` (the raw decoded JSON).
     * Actions that override `BaseAction::decode()` may return any type
     * (typed resources, value objects, etc.).
     */
    public function execute(): mixed;
}
