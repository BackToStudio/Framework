<?php

declare(strict_types=1);

namespace BackTo\Framework\Clock\Tests;

use BackTo\Framework\Clock\FrozenClock;
use BackTo\Framework\Clock\SystemClock;
use BackTo\Framework\Contracts\ClockInterface;
use PHPUnit\Framework\TestCase;

class ClockTest extends TestCase
{
    public function testSystemClockImplementsInterface(): void
    {
        $this->assertInstanceOf(ClockInterface::class, new SystemClock());
        $this->assertInstanceOf(\Psr\Clock\ClockInterface::class, new SystemClock());
    }

    public function testSystemClockReturnsCurrentTime(): void
    {
        $before = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));
        $now = (new SystemClock())->now();
        $after = new \DateTimeImmutable('now', new \DateTimeZone('UTC'));

        $this->assertGreaterThanOrEqual($before, $now);
        $this->assertLessThanOrEqual($after, $now);
    }

    public function testFrozenClockImplementsInterface(): void
    {
        $this->assertInstanceOf(ClockInterface::class, new FrozenClock());
        $this->assertInstanceOf(\Psr\Clock\ClockInterface::class, new FrozenClock());
    }

    public function testFrozenClockReturnsSameTime(): void
    {
        $fixed = new \DateTimeImmutable('2024-01-15 12:00:00', new \DateTimeZone('UTC'));
        $clock = new FrozenClock($fixed);

        $this->assertSame($fixed, $clock->now());
        $this->assertSame($fixed, $clock->now());
    }

    public function testFrozenClockTravel(): void
    {
        $clock = new FrozenClock(new \DateTimeImmutable('2024-01-15 12:00:00'));
        $clock->travel(new \DateInterval('PT1H'));

        $this->assertSame('2024-01-15 13:00:00', $clock->now()->format('Y-m-d H:i:s'));
    }

    public function testFrozenClockFreeze(): void
    {
        $clock = new FrozenClock();
        $newTime = new \DateTimeImmutable('2025-06-01 00:00:00');
        $clock->freeze($newTime);

        $this->assertSame($newTime, $clock->now());
    }
}
