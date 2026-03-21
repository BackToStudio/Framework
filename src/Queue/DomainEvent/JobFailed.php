<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\DomainEvent;

use BackTo\Framework\Contracts\DomainEventInterface;

/**
 * Raised when a queued job has failed execution.
 */
final readonly class JobFailed implements DomainEventInterface
{
    public function __construct(
        public int $jobId,
        public string $key,
        public string $group,
        public string $error,
        public int $attempts,
        public bool $willRetry,
        public \DateTimeImmutable $failedAt,
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->failedAt;
    }
}
