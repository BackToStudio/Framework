<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

/**
 * Marker interface for domain events.
 *
 * Domain events represent something meaningful that happened in the domain.
 * They are dispatched after a state change has been persisted, enabling
 * loose coupling between bounded contexts.
 *
 * @deprecated Since BackTo Framework 1.x. Domain events no longer need a marker interface.
 *             Use plain objects dispatched via {@see \BackTo\Framework\EventDispatcher\Contracts\EventDispatcherInterface}.
 *             Existing events can keep implementing this interface during the migration period,
 *             but new events should be plain classes. This interface will be removed in the next major version.
 */
interface DomainEventInterface
{
    /**
     * When the event occurred.
     */
    public function occurredAt(): \DateTimeImmutable;
}
