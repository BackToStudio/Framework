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
 * @covers \BackTo\Framework\Lock\LockFactory
 */
class LockFactoryTest extends TestCase
{
    private LockFactory $factory;

    protected function setUp(): void
    {
        $store = new InMemoryLockStore();
        $symfonyFactory = new SymfonyLockFactory($store);
        $this->factory = new LockFactory($symfonyFactory);
    }

    public function testImplementsLockFactoryInterface(): void
    {
        $this->assertInstanceOf(LockFactoryInterface::class, $this->factory);
    }

    public function testCreateReturnsLockInterface(): void
    {
        $lock = $this->factory->create('test-resource');

        $this->assertInstanceOf(LockInterface::class, $lock);
    }

    public function testCreateWithCustomTtl(): void
    {
        $lock = $this->factory->create('test-resource', 600);

        $this->assertSame('test-resource', $lock->getResource());
    }

    public function testCreateProducesIndependentLocks(): void
    {
        $lock1 = $this->factory->create('resource-a');
        $lock2 = $this->factory->create('resource-b');

        $lock1->acquire();

        $this->assertTrue($lock1->isAcquired());
        $this->assertFalse($lock2->isAcquired());
    }

    public function testTwoLocksOnSameResourceCompete(): void
    {
        $lock1 = $this->factory->create('shared');
        $lock2 = $this->factory->create('shared');

        $this->assertTrue($lock1->acquire());
        $this->assertFalse($lock2->acquire());

        $lock1->release();
        $this->assertTrue($lock2->acquire());
    }
}
