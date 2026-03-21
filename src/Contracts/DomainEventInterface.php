<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

/**
 * Marker interface for domain events.
 *
 * Domain events represent something meaningful that happened in the domain.
 * They are dispatched after a state change has been persisted, enabling
 * loose coupling between bounded contexts.
 */
interface DomainEventInterface
{
    /**
     * When the event occurred.
     */
    public function occurredAt(): \DateTimeImmutable;
}
