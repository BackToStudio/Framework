<?php

declare(strict_types=1);

namespace BackTo\Framework\Lock\Tests;

use BackTo\Framework\Cache\Contracts\CacheStoreInterface;
use BackTo\Framework\Lock\Contracts\LockStoreInterface;
use BackTo\Framework\Lock\Infrastructure\CacheStoreLockStore;
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

    public function testImplementsLockStoreInterface(): void
    {
        $data = new \ArrayObject();
        $store = $this->createStoreWithData($data);
        $this->assertInstanceOf(LockStoreInterface::class, $store);
    }

    public function testAcquireOnFreeResource(): void
    {
        $data = new \ArrayObject();
        $store = $this->createStoreWithData($data);

        $this->assertTrue($store->acquire('my-lock', 'token-1', 60));
        $this->assertTrue($store->exists('my-lock'));
    }

    public function testAcquireOnLockedResourceFails(): void
    {
        $data = new \ArrayObject();
        $store = $this->createStoreWithData($data);

        $store->acquire('my-lock', 'token-1', 60);
        $this->assertFalse($store->acquire('my-lock', 'token-2', 60));
    }

    public function testReentrantAcquire(): void
    {
        $data = new \ArrayObject();
        $store = $this->createStoreWithData($data);

        $store->acquire('my-lock', 'token-1', 60);
        $this->assertTrue($store->acquire('my-lock', 'token-1', 60));
    }

    public function testReleaseByOwner(): void
    {
        $data = new \ArrayObject();
        $store = $this->createStoreWithData($data);

        $store->acquire('my-lock', 'token-1', 60);
        $store->release('my-lock', 'token-1');

        $this->assertFalse($store->exists('my-lock'));
    }

    public function testReleaseByNonOwnerIsIgnored(): void
    {
        $data = new \ArrayObject();
        $store = $this->createStoreWithData($data);

        $store->acquire('my-lock', 'token-1', 60);
        $store->release('my-lock', 'token-2'); // wrong token

        $this->assertTrue($store->exists('my-lock'));
    }

    public function testExistsReturnsFalseForUnknownResource(): void
    {
        $data = new \ArrayObject();
        $store = $this->createStoreWithData($data);

        $this->assertFalse($store->exists('nonexistent'));
    }

    public function testReleaseNonexistentIsNoOp(): void
    {
        $data = new \ArrayObject();
        $store = $this->createStoreWithData($data);

        $store->release('nonexistent', 'token'); // Should not throw
        $this->assertFalse($store->exists('nonexistent'));
    }

    public function testCacheKeyIsPrefixed(): void
    {
        $cacheStore = $this->createMock(CacheStoreInterface::class);

        $cacheStore->method('get')->willReturn(false);
        $cacheStore->expects($this->once())
            ->method('set')
            ->with('backto_lock_my-resource', 'token-1', 60)
            ->willReturn(true);

        $store = new CacheStoreLockStore($cacheStore);
        $store->acquire('my-resource', 'token-1', 60);
    }

    public function testTtlIsPassedToCacheStore(): void
    {
        $cacheStore = $this->createMock(CacheStoreInterface::class);

        $cacheStore->method('get')->willReturn(false);
        $cacheStore->expects($this->once())
            ->method('set')
            ->with($this->anything(), $this->anything(), 120)
            ->willReturn(true);

        $store = new CacheStoreLockStore($cacheStore);
        $store->acquire('test', 'token', 120);
    }
}
