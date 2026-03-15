<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Tests;

use BackTo\Framework\Cache\Contracts\CacheInterface;
use BackTo\Framework\Cache\Contracts\InvalidArgumentException;
use BackTo\Framework\Cache\Strategy\TransientCache;
use PHPUnit\Framework\TestCase;

/**
 * Tests for TransientCache that don't require WordPress functions.
 *
 * The actual transient read/write is tested in integration tests with WordPress.
 * Here we test PSR-16 contract compliance (key validation, interface).
 */
class TransientCacheTest extends TestCase
{
    public function testImplementsCacheInterface(): void
    {
        $cache = new TransientCache();
        $this->assertInstanceOf(CacheInterface::class, $cache);
    }

    public function testDefaultPrefix(): void
    {
        $cache = new TransientCache();
        $this->assertInstanceOf(TransientCache::class, $cache);
    }

    public function testCustomPrefix(): void
    {
        $cache = new TransientCache('myapp_');
        $this->assertInstanceOf(TransientCache::class, $cache);
    }

    public function testInvalidKeyThrowsException(): void
    {
        $cache = new TransientCache();
        $this->expectException(InvalidArgumentException::class);
        $cache->get('invalid{key}');
    }

    public function testEmptyKeyThrowsException(): void
    {
        $cache = new TransientCache();
        $this->expectException(InvalidArgumentException::class);
        $cache->get('');
    }

    /**
     * @dataProvider invalidKeysProvider
     */
    public function testReservedCharactersThrowException(string $key): void
    {
        $cache = new TransientCache();
        $this->expectException(InvalidArgumentException::class);
        $cache->has($key);
    }

    public function invalidKeysProvider(): array
    {
        return [
            'curly braces' => ['foo{bar}'],
            'parentheses' => ['foo(bar)'],
            'slash' => ['foo/bar'],
            'at sign' => ['foo@bar'],
            'colon' => ['foo:bar'],
            'backslash' => ['foo\\bar'],
        ];
    }
}
