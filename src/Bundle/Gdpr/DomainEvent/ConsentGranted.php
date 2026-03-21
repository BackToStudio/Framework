<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Gdpr\DomainEvent;

use BackTo\Framework\Contracts\DomainEventInterface;

/**
 * Raised when a user grants consent for a specific tracking category.
 */
final readonly class ConsentGranted implements DomainEventInterface
{
    /**
     * @param string[] $categories The consent category keys that were granted.
     */
    public function __construct(
        public array $categories,
        public \DateTimeImmutable $grantedAt,
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->grantedAt;
    }
}
