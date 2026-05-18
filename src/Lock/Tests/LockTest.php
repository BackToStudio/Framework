<?php

declare(strict_types=1);

namespace BackTo\Framework\Lock\Tests;

use BackTo\Framework\Lock\Contracts\LockFactoryInterface;
use BackTo\Framework\Lock\Contracts\LockInterface;
use BackTo\Framework\Lock\Infrastructure\InMemoryLockStore;
use BackTo\Framework\Lock\LockFactory;
use BackToVendor\Symfony\Component\Lock\LockFactory as SymfonyLockFactory;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BackTo\Framework\Lock\Lock
 * @covers \BackTo\Framework\Lock\LockFactory
 */
class LockTest extends TestCase
{
    private LockFactory $factory;

    protected function setUp(): void
    {
        $store = new InMemoryLockStore();
        $symfonyFactory = new SymfonyLockFactory($store);
        $this->factory = new LockFactory($symfonyFactory);
    }

    public function testImplementsLockInterface(): void
    {
        $lock = $this->factory->create('test', 60);
        $this->assertInstanceOf(LockInterface::class, $lock);
    }

    public function testAcquireAndRelease(): void
    {
        $lock = $this->factory->create('my-resource', 60);

        $this->assertFalse($lock->isAcquired());
        $this->assertTrue($lock->acquire());
        $this->assertTrue($lock->isAcquired());

        $lock->release();
        $this->assertFalse($lock->isAcquired());
    }

    public function testAcquireTwiceReturnsTrueIfAlreadyOwned(): void
    {
        $lock = $this->factory->create('my-resource', 60);

        $this->assertTrue($lock->acquire());
        $this->assertTrue($lock->acquire());
    }

    public function testCannotAcquireIfHeldByAnother(): void
    {
        $lock1 = $this->factory->create('shared', 60);
        $lock2 = $this->factory->create('shared', 60);

        $this->assertTrue($lock1->acquire());
        $this->assertFalse($lock2->acquire());
    }

    public function testReleaseAllowsOtherToAcquire(): void
    {
        $lock1 = $this->factory->create('shared', 60);
        $lock2 = $this->factory->create('shared', 60);

        $lock1->acquire();
        $lock1->release();

        $this->assertTrue($lock2->acquire());
    }

    public function testReleaseWithoutAcquireIsNoOp(): void
    {
        $lock = $this->factory->create('my-resource', 60);
        $lock->release(); // Should not throw
        $this->assertFalse($lock->isAcquired());
    }

    public function testGetResource(): void
    {
        $lock = $this->factory->create('queue-processing', 60);
        $this->assertSame('queue-processing', $lock->getResource());
    }

    public function testEmptyResourceThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('must not be empty');

        $this->factory->create('', 60);
    }

    public function testZeroTtlThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('at least 1 second');

        $this->factory->create('test', 0);
    }

    public function testNegativeTtlThrows(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->factory->create('test', -10);
    }

    public function testDifferentResourcesAreIndependent(): void
    {
        $lockA = $this->factory->create('resource-a', 60);
        $lockB = $this->factory->create('resource-b', 60);

        $this->assertTrue($lockA->acquire());
        $this->assertTrue($lockB->acquire());
    }
}
