<?php

declare(strict_types=1);

namespace BackTo\Framework\EventDispatcher;

/**
 * Describes a single event listener binding.
 *
 * @internal Used by EventDispatcher to store listener metadata.
 */
final class ListenerDescriptor
{
    /** @var callable */
    private $listener;
    private int $priority;

    /**
     * @param callable $listener
     */
    public function __construct(callable $listener, int $priority = 0)
    {
        $this->listener = $listener;
        $this->priority = $priority;
    }

    public function getListener(): callable
    {
        return $this->listener;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }
}
