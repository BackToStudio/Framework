<?php

declare(strict_types=1);

namespace BackTo\Framework\EventDispatcher;

use BackTo\Framework\EventDispatcher\Contracts\StoppableEventInterface;

/**
 * Base event class with propagation stopping support.
 *
 * Extend this class for convenience, or implement StoppableEventInterface directly.
 */
class Event implements StoppableEventInterface
{
    private bool $propagationStopped = false;

    public function isPropagationStopped(): bool
    {
        return $this->propagationStopped;
    }

    public function stopPropagation(): void
    {
        $this->propagationStopped = true;
    }
}
