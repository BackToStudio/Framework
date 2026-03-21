<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

/**
 * Dispatches domain events to registered listeners.
 *
 * Implementations may delegate to the HookDispatcherInterface (WordPress hooks),
 * an in-memory bus, or any other event transport.
 */
interface DomainEventDispatcherInterface
{
    /**
     * Dispatch a domain event to all registered listeners.
     */
    public function dispatch(DomainEventInterface $event): void;
}
