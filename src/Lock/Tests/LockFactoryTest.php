<?php

declare(strict_types=1);

namespace BackTo\Framework\Lock\Tests;

use BackTo\Framework\Lock\Contracts\LockFactoryInterface;
use BackTo\Framework\Lock\Contracts\LockInterface;
use BackTo\Framework\Lock\Infrastructure\InMemoryLockStore;
use BackTo\Framework\Lock\LockFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BackTo\Framework\Lock\LockFactory
 */
class LockFactoryTest extends TestCase
{
    public function testImplementsLockFactoryInterface(): void
    {
        $factory = new LockFactory(new InMemoryLockStore());
        $this->assertInstanceOf(LockFactoryInterface::class, $factory);
    }

    public function testCreateReturnsLockInterface(): void
    {
        $factory = new LockFactory(new InMemoryLockStore());
        $lock = $factory->create('test-resource');

        $this->assertInstanceOf(LockInterface::class, $lock);
    }

    public function testCreateWithCustomTtl(): void
    {
        $factory = new LockFactory(new InMemoryLockStore());
        $lock = $factory->create('test-resource', 600);

        $this->assertSame('test-resource', $lock->getResource());
    }

    public function testCreateProducesIndependentLocks(): void
    {
        $factory = new LockFactory(new InMemoryLockStore());

        $lock1 = $factory->create('resource-a');
        $lock2 = $factory->create('resource-b');

        $lock1->acquire();

        $this->assertTrue($lock1->isAcquired());
        $this->assertFalse($lock2->isAcquired());
    }

    public function testTwoLocksOnSameResourceCompete(): void
    {
        $factory = new LockFactory(new InMemoryLockStore());

        $lock1 = $factory->create('shared');
        $lock2 = $factory->create('shared');

        $this->assertTrue($lock1->acquire());
        $this->assertFalse($lock2->acquire());

        $lock1->release();
        $this->assertTrue($lock2->acquire());
    }
}
