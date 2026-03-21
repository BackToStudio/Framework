<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\DomainEvent;

use BackTo\Framework\Contracts\DomainEventInterface;

/**
 * Raised when a queued job has been successfully completed.
 */
final readonly class JobCompleted implements DomainEventInterface
{
    public function __construct(
        public int $jobId,
        public string $key,
        public string $group,
        public \DateTimeImmutable $completedAt,
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->completedAt;
    }
}
