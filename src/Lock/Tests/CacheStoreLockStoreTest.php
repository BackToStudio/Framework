<?php

declare(strict_types=1);

namespace BackTo\Framework\Lock\Tests;

use BackTo\Framework\Cache\Contracts\CacheStoreInterface;
use BackTo\Framework\Lock\Infrastructure\CacheStoreLockStore;
use BackToVendor\Symfony\Component\Lock\Exception\LockConflictedException;
use BackToVendor\Symfony\Component\Lock\Key;
use BackToVendor\Symfony\Component\Lock\PersistingStoreInterface;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BackTo\Framework\Lock\Infrastructure\CacheStoreLockStore
 */
class CacheStoreLockStoreTest extends TestCase
{
    private function createStoreWithData(\ArrayObject $data): CacheStoreLockStore
    {
        $cacheStore = $this->createMock(CacheStoreInterface::class);

        $cacheStore->method('get')
            ->willReturnCallback(fn (string $key) => $data[$key] ?? false);

        $cacheStore->method('set')
            ->willReturnCallback(function (string $key, mixed $value) use ($data): bool {
                $data[$key] = $value;
                return true;
            });

        $cacheStore->method('delete')
            ->willReturnCallback(function (string $key) use ($data): bool {
                unset($data[$key]);
                return true;
            });

        return new CacheStoreLockStore($cacheStore);
    }

    public function testImplementsPersistingStoreInterface(): void
    {
        $data = new \ArrayObject();
        $store = $this->createStoreWithData($data);
        $this->assertInstanceOf(PersistingStoreInterface::class, $store);
    }

    public function testSaveOnFreeResource(): void
    {
        $data = new \ArrayObject();
        $store = $this->createStoreWithData($data);
        $key = new Key('my-lock');

        $store->save($key);

        $this->assertTrue($store->exists($key));
    }

    public function testSaveOnLockedResourceThrows(): void
    {
        $data = new \ArrayObject();
        $store = $this->createStoreWithData($data);
        $key1 = new Key('my-lock');
        $key2 = new Key('my-lock');

        $store->save($key1);

        $this->expectException(LockConflictedException::class);
        $store->save($key2);
    }

    public function testReentrantSave(): void
    {
        $data = new \ArrayObject();
        $store = $this->createStoreWithData($data);
        $key = new Key('my-lock');

        $store->save($key);
        $store->save($key); // Same key = same token, should not throw

        $this->assertTrue($store->exists($key));
    }

    public function testDeleteByOwner(): void
    {
        $data = new \ArrayObject();
        $store = $this->createStoreWithData($data);
        $key = new Key('my-lock');

        $store->save($key);
        $store->delete($key);

        $this->assertFalse($store->exists($key));
    }

    public function testDeleteByNonOwnerIsIgnored(): void
    {
        $data = new \ArrayObject();
        $store = $this->createStoreWithData($data);
        $key1 = new Key('my-lock');
        $key2 = new Key('my-lock');

        $store->save($key1);
        $store->delete($key2); // Different token

        $this->assertTrue($store->exists($key1));
    }

    public function testExistsReturnsFalseForUnknownResource(): void
    {
        $data = new \ArrayObject();
        $store = $this->createStoreWithData($data);
        $key = new Key('nonexistent');

        $this->assertFalse($store->exists($key));
    }

    public function testDeleteNonexistentIsNoOp(): void
    {
        $data = new \ArrayObject();
        $store = $this->createStoreWithData($data);
        $key = new Key('nonexistent');

        $store->delete($key); // Should not throw
        $this->assertFalse($store->exists($key));
    }

    public function testCacheKeyIsPrefixed(): void
    {
        $cacheStore = $this->createMock(CacheStoreInterface::class);

        $cacheStore->method('get')->willReturn(false);
        $cacheStore->expects($this->once())
            ->method('set')
            ->with(
                $this->stringStartsWith('backto_lock_my-resource'),
                $this->isType('string'),
                300
            )
            ->willReturn(true);

        $store = new CacheStoreLockStore($cacheStore);
        $key = new Key('my-resource');
        $store->save($key);
    }

    public function testPutOffExpiration(): void
    {
        $data = new \ArrayObject();
        $store = $this->createStoreWithData($data);
        $key = new Key('my-lock');

        $store->save($key);
        $store->putOffExpiration($key, 600.0);

        $this->assertTrue($store->exists($key));
    }

    public function testPutOffExpirationThrowsForNonOwner(): void
    {
        $data = new \ArrayObject();
        $store = $this->createStoreWithData($data);
        $key1 = new Key('my-lock');
        $key2 = new Key('my-lock');

        $store->save($key1);

        $this->expectException(LockConflictedException::class);
        $store->putOffExpiration($key2, 600.0);
    }
}
