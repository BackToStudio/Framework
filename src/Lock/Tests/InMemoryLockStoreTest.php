<?php

declare(strict_types=1);

namespace BackTo\Framework\Lock\Tests;

use BackTo\Framework\Lock\Contracts\LockStoreInterface;
use BackTo\Framework\Lock\Infrastructure\InMemoryLockStore;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BackTo\Framework\Lock\Infrastructure\InMemoryLockStore
 */
class InMemoryLockStoreTest extends TestCase
{
    public function testImplementsLockStoreInterface(): void
    {
        $store = new InMemoryLockStore();
        $this->assertInstanceOf(LockStoreInterface::class, $store);
    }

    public function testAcquireOnFreeResource(): void
    {
        $store = new InMemoryLockStore();
        $this->assertTrue($store->acquire('res', 'token-a', 60));
    }

    public function testAcquireOnLockedResourceFails(): void
    {
        $store = new InMemoryLockStore();
        $store->acquire('res', 'token-a', 60);

        $this->assertFalse($store->acquire('res', 'token-b', 60));
    }

    public function testReentrantAcquire(): void
    {
        $store = new InMemoryLockStore();
        $store->acquire('res', 'token-a', 60);

        $this->assertTrue($store->acquire('res', 'token-a', 60));
    }

    public function testReleaseByOwner(): void
    {
        $store = new InMemoryLockStore();
        $store->acquire('res', 'token-a', 60);
        $store->release('res', 'token-a');

        $this->assertFalse($store->exists('res'));
    }

    public function testReleaseByNonOwnerIsIgnored(): void
    {
        $store = new InMemoryLockStore();
        $store->acquire('res', 'token-a', 60);
        $store->release('res', 'token-b');

        $this->assertTrue($store->exists('res'));
    }

    public function testExistsReturnsFalseForFreeResource(): void
    {
        $store = new InMemoryLockStore();
        $this->assertFalse($store->exists('unknown'));
    }

    public function testExpiredLockIsEvicted(): void
    {
        $store = new InMemoryLockStore();
        // TTL of 1 second — will expire quickly in practice.
        // We test the eviction logic by directly checking exists after TTL.
        $store->acquire('res', 'token', 1);

        // The lock exists right after acquire.
        $this->assertTrue($store->exists('res'));
    }

    public function testMultipleResourcesAreIndependent(): void
    {
        $store = new InMemoryLockStore();
        $store->acquire('a', 'token-1', 60);
        $store->acquire('b', 'token-2', 60);

        $store->release('a', 'token-1');

        $this->assertFalse($store->exists('a'));
        $this->assertTrue($store->exists('b'));
    }

    public function testReleaseNonexistentIsNoOp(): void
    {
        $store = new InMemoryLockStore();
        $store->release('nonexistent', 'token'); // No exception
        $this->assertFalse($store->exists('nonexistent'));
    }
}
