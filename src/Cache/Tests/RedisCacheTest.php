<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Tests;

use BackTo\Framework\Cache\Contracts\CacheInterface;
use BackTo\Framework\Cache\Contracts\InvalidArgumentException;
use BackTo\Framework\Cache\Contracts\RedisClientInterface;
use BackTo\Framework\Cache\Strategy\RedisCache;
use PHPUnit\Framework\TestCase;

class RedisCacheTest extends TestCase
{
    private RedisClientInterface $client;
    private RedisCache $cache;

    protected function setUp(): void
    {
        $this->client = $this->createMock(RedisClientInterface::class);
        $this->cache = new RedisCache($this->client, 'test_');
    }

    public function testImplementsCacheInterface(): void
    {
        $this->assertInstanceOf(CacheInterface::class, $this->cache);
    }

    public function testGetReturnsValueOnHit(): void
    {
        $this->client->method('get')
            ->with('test_my_key')
            ->willReturn(\serialize('hello'));

        $this->assertSame('hello', $this->cache->get('my_key'));
    }

    public function testGetReturnsDefaultOnMiss(): void
    {
        $this->client->method('get')->willReturn(null);

        $this->assertSame('fallback', $this->cache->get('missing', 'fallback'));
    }

    public function testGetReturnsNullDefaultOnMiss(): void
    {
        $this->client->method('get')->willReturn(null);

        $this->assertNull($this->cache->get('missing'));
    }

    public function testSetWithTtl(): void
    {
        $this->client->expects($this->once())
            ->method('set')
            ->with('test_key', \serialize('value'), 300)
            ->willReturn(true);

        $this->assertTrue($this->cache->set('key', 'value', 300));
    }

    public function testSetWithoutTtl(): void
    {
        $this->client->expects($this->once())
            ->method('set')
            ->with('test_key', \serialize('value'), 0)
            ->willReturn(true);

        $this->assertTrue($this->cache->set('key', 'value'));
    }

    public function testSetWithZeroTtlDeletesKey(): void
    {
        $this->client->expects($this->once())
            ->method('del')
            ->with('test_key')
            ->willReturn(true);

        $this->assertTrue($this->cache->set('key', 'value', 0));
    }

    public function testSetWithNegativeTtlDeletesKey(): void
    {
        $this->client->expects($this->once())
            ->method('del')
            ->with('test_key')
            ->willReturn(true);

        $this->assertTrue($this->cache->set('key', 'value', -1));
    }

    public function testDelete(): void
    {
        $this->client->expects($this->once())
            ->method('del')
            ->with('test_key')
            ->willReturn(true);

        $this->assertTrue($this->cache->delete('key'));
    }

    public function testClear(): void
    {
        $this->client->expects($this->once())
            ->method('flushByPrefix')
            ->with('test_')
            ->willReturn(true);

        $this->assertTrue($this->cache->clear());
    }

    public function testHas(): void
    {
        $this->client->method('exists')
            ->with('test_key')
            ->willReturn(true);

        $this->assertTrue($this->cache->has('key'));
    }

    public function testHasReturnsFalse(): void
    {
        $this->client->method('exists')->willReturn(false);

        $this->assertFalse($this->cache->has('missing'));
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

    public function testGetMultiple(): void
    {
        $this->client->method('get')
            ->willReturnMap([
                ['test_a', \serialize(1)],
                ['test_b', null],
            ]);

        $result = $this->cache->getMultiple(['a', 'b'], 'default');

        $this->assertSame(1, $result['a']);
        $this->assertSame('default', $result['b']);
    }

    public function testSetMultiple(): void
    {
        $this->client->expects($this->exactly(2))
            ->method('set')
            ->willReturn(true);

        $this->assertTrue($this->cache->setMultiple(['a' => 1, 'b' => 2], 60));
    }

    public function testDeleteMultiple(): void
    {
        $this->client->expects($this->exactly(2))
            ->method('del')
            ->willReturn(true);

        $this->assertTrue($this->cache->deleteMultiple(['a', 'b']));
    }

    public function testSetWithDateInterval(): void
    {
        $this->client->expects($this->once())
            ->method('set')
            ->with(
                'test_key',
                $this->anything(),
                $this->greaterThanOrEqual(3599),
            )
            ->willReturn(true);

        $this->assertTrue($this->cache->set('key', 'value', new \DateInterval('PT1H')));
    }

    public function testComplexValuesAreSerialized(): void
    {
        $complex = ['nested' => ['data' => true], 'count' => 42];

        $this->client->expects($this->once())
            ->method('set')
            ->with('test_key', \serialize($complex), 0)
            ->willReturn(true);

        $this->cache->set('key', $complex);
    }

    public function testComplexValuesAreDeserialized(): void
    {
        $complex = ['nested' => ['data' => true], 'count' => 42];

        $this->client->method('get')
            ->willReturn(\serialize($complex));

        $this->assertSame($complex, $this->cache->get('key'));
    }
}
