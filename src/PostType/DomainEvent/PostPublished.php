<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\DomainEvent;

use BackTo\Framework\Contracts\DomainEventInterface;

/**
 * Raised when a post transitions to the "publish" status.
 */
final readonly class PostPublished implements DomainEventInterface
{
    public function __construct(
        public int $postId,
        public string $postType,
        public \DateTimeImmutable $publishedAt,
    ) {
    }

    public function occurredAt(): \DateTimeImmutable
    {
        return $this->publishedAt;
    }
}
