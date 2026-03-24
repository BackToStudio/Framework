<?php

declare(strict_types=1);

namespace BackTo\Framework\EventDispatcher\Contracts;

/**
 * An event whose propagation can be stopped.
 *
 * When isPropagationStopped() returns true, the dispatcher skips
 * all remaining listeners for this event.
 *
 * Compatible with PSR-14 StoppableEventInterface.
 */
interface StoppableEventInterface
{
    public function isPropagationStopped(): bool;
}
