<?php

declare(strict_types=1);

namespace BackTo\Framework\EventDispatcher\Contracts;

/**
 * Dispatches events to registered listeners and subscribers.
 *
 * Port interface — domain and application layers depend on this contract,
 * while the concrete implementation wires into the listener registry.
 */
interface EventDispatcherInterface
{
    /**
     * Dispatch an event to all registered listeners.
     *
     * Listeners are called in priority order. If the event implements
     * StoppableEventInterface and propagation is stopped, remaining
     * listeners are skipped.
     *
     * @template T of object
     * @param T $event
     * @return T The (potentially modified) event
     */
    public function dispatch(object $event): object;
}
