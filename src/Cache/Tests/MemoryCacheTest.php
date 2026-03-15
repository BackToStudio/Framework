<?php

namespace BackTo\Framework\Cache\Tests;

use BackTo\Framework\Cache\Contracts\CacheInterface;
use BackTo\Framework\Cache\Contracts\InvalidArgumentException;
use BackTo\Framework\Cache\Strategy\MemoryCache;
use DateInterval;
use PHPUnit\Framework\TestCase;

class MemoryCacheTest extends TestCase
{
    private MemoryCache $cache;

    protected function setUp(): void
    {
        $this->cache = new MemoryCache();
    }

    public function testImplementsCacheInterface(): void
    {
        $this->assertInstanceOf(CacheInterface::class, $this->cache);
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
        $this->cache->set('null', null);

        $this->assertSame(42, $this->cache->get('int'));
        $this->assertSame(['a', 'b'], $this->cache->get('array'));
        $this->assertFalse($this->cache->get('bool'));
        $this->assertNull($this->cache->get('null', 'not-null'));
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

    public function testTtlExpiresEntry(): void
    {
        $this->cache->set('short', 'value', 1);
        $this->assertTrue($this->cache->has('short'));

        // We can't easily test real expiration without sleeping,
        // but we can test that a zero/negative TTL deletes immediately.
        $this->cache->set('expired', 'value', 0);
        $this->assertFalse($this->cache->has('expired'));

        $this->cache->set('negative', 'value', -1);
        $this->assertFalse($this->cache->has('negative'));
    }

    public function testTtlWithDateInterval(): void
    {
        $interval = new DateInterval('PT1H'); // 1 hour
        $this->cache->set('interval', 'value', $interval);
        $this->assertTrue($this->cache->has('interval'));
        $this->assertSame('value', $this->cache->get('interval'));
    }

    public function testNullTtlMeansNoExpiration(): void
    {
        $this->cache->set('forever', 'value', null);
        $this->assertTrue($this->cache->has('forever'));
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

    /**
     * @dataProvider invalidKeysProvider
     */
    public function testReservedCharactersThrowException(string $key): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->cache->set($key, 'value');
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
