<?php

declare(strict_types=1);

namespace BackTo\Framework\Clock;

use BackTo\Framework\Contracts\ClockInterface;

/**
 * System clock returning the real current time.
 */
final class SystemClock implements ClockInterface
{
    public function __construct(
        private readonly \DateTimeZone $timezone = new \DateTimeZone('UTC'),
    ) {}

    public function now(): \DateTimeImmutable
    {
        return new \DateTimeImmutable('now', $this->timezone);
    }
}
