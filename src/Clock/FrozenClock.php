<?php

declare(strict_types=1);

namespace BackTo\Framework\Clock;

use BackTo\Framework\Contracts\ClockInterface;

/**
 * Clock frozen at a fixed point in time.
 *
 * Useful for deterministic testing of time-dependent code.
 */
final class FrozenClock implements ClockInterface
{
    private \DateTimeImmutable $now;

    public function __construct(?\DateTimeImmutable $now = null)
    {
        $this->now = $now ?? new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
    }

    public function now(): \DateTimeImmutable
    {
        return $this->now;
    }

    public function travel(\DateInterval $interval): void
    {
        $this->now = $this->now->add($interval);
    }

    public function freeze(\DateTimeImmutable $now): void
    {
        $this->now = $now;
    }
}
