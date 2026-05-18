<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

/**
 * Dispatches domain events to registered listeners.
 *
 * Implementations may delegate to the HookDispatcherInterface (WordPress hooks),
 * an in-memory bus, or any other event transport.
 *
 * @deprecated Since BackTo Framework 1.x. Use {@see \BackTo\Framework\EventDispatcher\Contracts\EventDispatcherInterface} instead.
 *             The EventDispatcher module provides a full-featured event system with subscribers,
 *             priority-based listeners, and automatic WordPress hook bridging.
 *             This interface will be removed in the next major version.
 */
interface DomainEventDispatcherInterface
{
    /**
     * Dispatch a domain event to all registered listeners.
     */
    public function dispatch(DomainEventInterface $event): void;
}
