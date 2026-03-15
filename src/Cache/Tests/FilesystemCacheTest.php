<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Tests;

use BackTo\Framework\Cache\Contracts\CacheInterface;
use BackTo\Framework\Cache\Contracts\InvalidArgumentException;
use BackTo\Framework\Cache\Strategy\FilesystemCache;
use DateInterval;
use PHPUnit\Framework\TestCase;

class FilesystemCacheTest extends TestCase
{
    private FilesystemCache $cache;
    private string $cacheDir;

    protected function setUp(): void
    {
        $this->cacheDir = sys_get_temp_dir() . '/btf_cache_test_' . uniqid();
        $this->cache = new FilesystemCache($this->cacheDir);
    }

    protected function tearDown(): void
    {
        $this->cache->clear();
        if (is_dir($this->cacheDir)) {
            rmdir($this->cacheDir);
        }
    }

    public function testImplementsCacheInterface(): void
    {
        $this->assertInstanceOf(CacheInterface::class, $this->cache);
    }

    public function testCreatesCacheDirectory(): void
    {
        $this->assertDirectoryExists($this->cacheDir);
    }

    public function testGetReturnsDefaultWhenKeyNotFound(): void
    {
        $this->assertNull($this->cache->get('missing'));
        $this->assertSame('fallback', $this->cache->get('missing', 'fallback'));
    }

    public function testSetAndGet(): void
    {
        $this->assertTrue($this->cache->set('key', 'value'));
        $this->assertSame('value', $this->cache->get('key'));
    }

    public function testSetOverwritesExistingValue(): void
    {
        $this->cache->set('key', 'first');
        $this->cache->set('key', 'second');
        $this->assertSame('second', $this->cache->get('key'));
    }

    public function testSetWithDifferentTypes(): void
    {
        $this->cache->set('int', 42);
        $this->cache->set('array', ['a', 'b']);
        $this->cache->set('bool', false);

        $this->assertSame(42, $this->cache->get('int'));
        $this->assertSame(['a', 'b'], $this->cache->get('array'));
        $this->assertFalse($this->cache->get('bool'));
    }

    public function testHas(): void
    {
        $this->assertFalse($this->cache->has('key'));
        $this->cache->set('key', 'value');
        $this->assertTrue($this->cache->has('key'));
    }

    public function testDelete(): void
    {
        $this->cache->set('key', 'value');
        $this->assertTrue($this->cache->delete('key'));
        $this->assertFalse($this->cache->has('key'));
    }

    public function testDeleteNonExistentKeyReturnsTrue(): void
    {
        $this->assertTrue($this->cache->delete('nope'));
    }

    public function testClear(): void
    {
        $this->cache->set('a', 1);
        $this->cache->set('b', 2);
        $this->assertTrue($this->cache->clear());
        $this->assertFalse($this->cache->has('a'));
        $this->assertFalse($this->cache->has('b'));
    }

    public function testGetMultiple(): void
    {
        $this->cache->set('a', 1);
        $this->cache->set('b', 2);

        $result = $this->cache->getMultiple(['a', 'b', 'c'], 'default');

        $this->assertSame(1, $result['a']);
        $this->assertSame(2, $result['b']);
        $this->assertSame('default', $result['c']);
    }

    public function testSetMultiple(): void
    {
        $this->assertTrue($this->cache->setMultiple(['x' => 10, 'y' => 20]));
        $this->assertSame(10, $this->cache->get('x'));
        $this->assertSame(20, $this->cache->get('y'));
    }

    public function testDeleteMultiple(): void
    {
        $this->cache->set('a', 1);
        $this->cache->set('b', 2);
        $this->cache->set('c', 3);

        $this->assertTrue($this->cache->deleteMultiple(['a', 'c']));
        $this->assertFalse($this->cache->has('a'));
        $this->assertTrue($this->cache->has('b'));
        $this->assertFalse($this->cache->has('c'));
    }

    public function testZeroTtlDeletesEntry(): void
    {
        $this->cache->set('expired', 'value', 0);
        $this->assertFalse($this->cache->has('expired'));
    }

    public function testNegativeTtlDeletesEntry(): void
    {
        $this->cache->set('expired', 'value', -5);
        $this->assertFalse($this->cache->has('expired'));
    }

    public function testTtlWithDateInterval(): void
    {
        $interval = new DateInterval('PT1H');
        $this->cache->set('interval', 'value', $interval);
        $this->assertTrue($this->cache->has('interval'));
        $this->assertSame('value', $this->cache->get('interval'));
    }

    public function testNullTtlMeansNoExpiration(): void
    {
        $this->cache->set('forever', 'value', null);
        $this->assertTrue($this->cache->has('forever'));
    }

    public function testCorruptedFileReturnsDefault(): void
    {
        $this->cache->set('key', 'value');

        // Corrupt the cache file
        $files = glob($this->cacheDir . '/*.cache');
        file_put_contents($files[0], 'not-serialized-data');

        $this->assertSame('default', $this->cache->get('key', 'default'));
    }

    public function testInvalidKeyThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cache->get('invalid{key}');
    }

    public function testEmptyKeyThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cache->get('');
    }
}
