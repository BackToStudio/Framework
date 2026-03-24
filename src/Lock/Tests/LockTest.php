<?php

declare(strict_types=1);

namespace BackTo\Framework\Lock\Tests;

use BackTo\Framework\Lock\Contracts\LockInterface;
use BackTo\Framework\Lock\Infrastructure\InMemoryLockStore;
use BackTo\Framework\Lock\Lock;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BackTo\Framework\Lock\Lock
 */
class LockTest extends TestCase
{
    private InMemoryLockStore $store;

    protected function setUp(): void
    {
        $this->store = new InMemoryLockStore();
    }

    public function testImplementsLockInterface(): void
    {
        $lock = new Lock($this->store, 'test', 60);
        $this->assertInstanceOf(LockInterface::class, $lock);
    }

    public function testAcquireAndRelease(): void
    {
        $lock = new Lock($this->store, 'my-resource', 60);

        $this->assertFalse($lock->isAcquired());
        $this->assertTrue($lock->acquire());
        $this->assertTrue($lock->isAcquired());

        $lock->release();
        $this->assertFalse($lock->isAcquired());
    }

    public function testAcquireTwiceReturnsTrueIfAlreadyOwned(): void
    {
        $lock = new Lock($this->store, 'my-resource', 60);

        $this->assertTrue($lock->acquire());
        $this->assertTrue($lock->acquire());
    }

    public function testCannotAcquireIfHeldByAnother(): void
    {
        $lock1 = new Lock($this->store, 'shared', 60, 'token-a');
        $lock2 = new Lock($this->store, 'shared', 60, 'token-b');

        $this->assertTrue($lock1->acquire());
        $this->assertFalse($lock2->acquire());
    }

    public function testReleaseAllowsOtherToAcquire(): void
    {
        $lock1 = new Lock($this->store, 'shared', 60, 'token-a');
        $lock2 = new Lock($this->store, 'shared', 60, 'token-b');

        $lock1->acquire();
        $lock1->release();

        $this->assertTrue($lock2->acquire());
    }

    public function testReleaseWithoutAcquireIsNoOp(): void
    {
        $lock = new Lock($this->store, 'my-resource', 60);
        $lock->release(); // Should not throw
        $this->assertFalse($lock->isAcquired());
    }

    public function testGetResource(): void
    {
        $lock = new Lock($this->store, 'queue-processing', 60);
        $this->assertSame('queue-processing', $lock->getResource());
    }

    public function testEmptyResourceThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be empty');

        new Lock($this->store, '', 60);
    }

    public function testZeroTtlThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('at least 1 second');

        new Lock($this->store, 'test', 0);
    }

    public function testNegativeTtlThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Lock($this->store, 'test', -10);
    }

    public function testDifferentResourcesAreIndependent(): void
    {
        $lockA = new Lock($this->store, 'resource-a', 60);
        $lockB = new Lock($this->store, 'resource-b', 60);

        $this->assertTrue($lockA->acquire());
        $this->assertTrue($lockB->acquire());
    }
}
