<?php

declare(strict_types=1);

namespace BackTo\Framework\Lock\Tests;

use BackTo\Framework\Lock\Infrastructure\InMemoryLockStore;
use BackToVendor\Symfony\Component\Lock\Exception\LockConflictedException;
use BackToVendor\Symfony\Component\Lock\Key;
use BackToVendor\Symfony\Component\Lock\PersistingStoreInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BackTo\Framework\Lock\Infrastructure\InMemoryLockStore
 */
class InMemoryLockStoreTest extends TestCase
{
    public function testImplementsPersistingStoreInterface(): void
    {
        $store = new InMemoryLockStore();
        $this->assertInstanceOf(PersistingStoreInterface::class, $store);
    }

    public function testSaveOnFreeResource(): void
    {
        $store = new InMemoryLockStore();
        $key = new Key('res');

        $store->save($key);

        $this->assertTrue($store->exists($key));
    }

    public function testSaveOnLockedResourceThrows(): void
    {
        $store = new InMemoryLockStore();
        $key1 = new Key('res');
        $key2 = new Key('res');

        $store->save($key1);

        $this->expectException(LockConflictedException::class);
        $store->save($key2);
    }

    public function testReentrantSave(): void
    {
        $store = new InMemoryLockStore();
        $key = new Key('res');

        $store->save($key);
        $store->save($key); // Same key, should not throw

        $this->assertTrue($store->exists($key));
    }

    public function testDeleteByOwner(): void
    {
        $store = new InMemoryLockStore();
        $key = new Key('res');

        $store->save($key);
        $store->delete($key);

        $this->assertFalse($store->exists($key));
    }

    public function testDeleteByNonOwnerIsIgnored(): void
    {
        $store = new InMemoryLockStore();
        $key1 = new Key('res');
        $key2 = new Key('res');

        $store->save($key1);
        $store->delete($key2); // Different key instance = different token

        $this->assertTrue($store->exists($key1));
    }

    public function testExistsReturnsFalseForFreeResource(): void
    {
        $store = new InMemoryLockStore();
        $key = new Key('unknown');

        $this->assertFalse($store->exists($key));
    }

    public function testMultipleResourcesAreIndependent(): void
    {
        $store = new InMemoryLockStore();
        $keyA = new Key('a');
        $keyB = new Key('b');

        $store->save($keyA);
        $store->save($keyB);

        $store->delete($keyA);

        $this->assertFalse($store->exists($keyA));
        $this->assertTrue($store->exists($keyB));
    }

    public function testDeleteNonexistentIsNoOp(): void
    {
        $store = new InMemoryLockStore();
        $key = new Key('nonexistent');

        $store->delete($key); // No exception
        $this->assertFalse($store->exists($key));
    }

    public function testPutOffExpiration(): void
    {
        $store = new InMemoryLockStore();
        $key = new Key('res');

        $store->save($key);
        $store->putOffExpiration($key, 600.0);

        $this->assertTrue($store->exists($key));
    }

    public function testPutOffExpirationThrowsForNonOwner(): void
    {
        $store = new InMemoryLockStore();
        $key1 = new Key('res');
        $key2 = new Key('res');

        $store->save($key1);

        $this->expectException(LockConflictedException::class);
        $store->putOffExpiration($key2, 600.0);
    }
}
