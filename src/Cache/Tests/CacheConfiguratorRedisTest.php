<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Tests;

use BackTo\Framework\Cache\CacheConfigurator;
use PHPUnit\Framework\TestCase;

class CacheConfiguratorRedisTest extends TestCase
{
    private CacheConfigurator $configurator;

    protected function setUp(): void
    {
        $this->configurator = new CacheConfigurator();
    }

    public function testStrategy(): void
    {
        $result = $this->configurator->strategy('redis');

        $this->assertSame($this->configurator, $result);
        $this->assertSame('redis', $this->configurator->toParameters()['cache.strategy']);
    }

    public function testRedisHost(): void
    {
        $this->configurator->redisHost('redis.example.com');
        $this->assertSame('redis.example.com', $this->configurator->toParameters()['cache.redis.host']);
    }

    public function testRedisPort(): void
    {
        $this->configurator->redisPort(6380);
        $this->assertSame(6380, $this->configurator->toParameters()['cache.redis.port']);
    }

    public function testRedisPassword(): void
    {
        $this->configurator->redisPassword('secret');
        $this->assertSame('secret', $this->configurator->toParameters()['cache.redis.password']);
    }

    public function testRedisDatabase(): void
    {
        $this->configurator->redisDatabase(2);
        $this->assertSame(2, $this->configurator->toParameters()['cache.redis.database']);
    }

    public function testRedisTimeout(): void
    {
        $this->configurator->redisTimeout(5.0);
        $this->assertSame(5.0, $this->configurator->toParameters()['cache.redis.timeout']);
    }

    public function testRedisPrefix(): void
    {
        $this->configurator->redisPrefix('myapp_');
        $this->assertSame('myapp_', $this->configurator->toParameters()['cache.redis.prefix']);
    }

    public function testRedisShorthand(): void
    {
        $params = $this->configurator
            ->redis('redis.local', 6380, 'pass', 2)
            ->toParameters();

        $this->assertSame('redis', $params['cache.strategy']);
        $this->assertSame('redis.local', $params['cache.redis.host']);
        $this->assertSame(6380, $params['cache.redis.port']);
        $this->assertSame('pass', $params['cache.redis.password']);
        $this->assertSame(2, $params['cache.redis.database']);
    }

    public function testFluentChaining(): void
    {
        $params = $this->configurator
            ->strategy('redis')
            ->redisHost('10.0.0.1')
            ->redisPort(6379)
            ->redisPassword('secret')
            ->redisDatabase(1)
            ->redisTimeout(3.0)
            ->redisPrefix('wp_')
            ->ttl(7200)
            ->toParameters();

        $this->assertCount(8, $params);
        $this->assertSame('redis', $params['cache.strategy']);
        $this->assertSame(7200, $params['cache.ttl']);
    }
}
