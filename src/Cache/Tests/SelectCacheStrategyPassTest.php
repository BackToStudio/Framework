<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Tests;

use BackTo\Framework\Cache\Contracts\CacheInterface;
use BackTo\Framework\Cache\Contracts\RedisClientInterface;
use BackTo\Framework\Cache\DependencyInjection\Compiler\SelectCacheStrategyPass;
use BackTo\Framework\Cache\HealthCheck\RedisHealthCheck;
use BackTo\Framework\Cache\Infrastructure\PhpRedisClient;
use BackTo\Framework\Cache\Strategy\FilesystemCache;
use BackTo\Framework\Cache\Strategy\MemoryCache;
use BackTo\Framework\Cache\Strategy\RedisCache;
use BackTo\Framework\Cache\Strategy\TransientCache;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use BackToVendor\Symfony\Component\DependencyInjection\Definition;
use PHPUnit\Framework\TestCase;

class SelectCacheStrategyPassTest extends TestCase
{
    private ContainerBuilder $container;
    private SelectCacheStrategyPass $pass;

    protected function setUp(): void
    {
        $this->container = new ContainerBuilder();
        $this->pass = new SelectCacheStrategyPass();
    }

    public function testDefaultsToTransientStrategy(): void
    {
        $this->container->setDefinition(TransientCache::class, new Definition(TransientCache::class));

        $this->pass->process($this->container);

        $this->assertSame(TransientCache::class, (string) $this->container->getAlias(CacheInterface::class));
    }

    public function testSelectsMemoryStrategy(): void
    {
        $this->container->setParameter('cache.strategy', 'memory');
        $this->container->setDefinition(MemoryCache::class, new Definition(MemoryCache::class));

        $this->pass->process($this->container);

        $this->assertSame(MemoryCache::class, (string) $this->container->getAlias(CacheInterface::class));
    }

    public function testSelectsFilesystemStrategy(): void
    {
        $this->container->setParameter('cache.strategy', 'filesystem');
        $this->container->setDefinition(FilesystemCache::class, new Definition(FilesystemCache::class));

        $this->pass->process($this->container);

        $this->assertSame(FilesystemCache::class, (string) $this->container->getAlias(CacheInterface::class));
    }

    public function testSelectsRedisStrategyAndRegistersServices(): void
    {
        $this->container->setParameter('cache.strategy', 'redis');
        $this->container->setParameter('cache.redis.host', '127.0.0.1');
        $this->container->setParameter('cache.redis.port', 6379);
        $this->container->setParameter('cache.redis.password', '');
        $this->container->setParameter('cache.redis.database', 0);
        $this->container->setParameter('cache.redis.timeout', 2.0);
        $this->container->setParameter('cache.redis.prefix', 'btf_');

        $this->pass->process($this->container);

        $this->assertSame(RedisCache::class, (string) $this->container->getAlias(CacheInterface::class));
        $this->assertTrue($this->container->hasDefinition(PhpRedisClient::class));
        $this->assertTrue($this->container->hasAlias(RedisClientInterface::class));
        $this->assertTrue($this->container->hasDefinition(RedisCache::class));
        $this->assertTrue($this->container->hasDefinition(RedisHealthCheck::class));
    }

    public function testRedisHealthCheckIsTagged(): void
    {
        $this->container->setParameter('cache.strategy', 'redis');
        $this->container->setParameter('cache.redis.host', '127.0.0.1');
        $this->container->setParameter('cache.redis.port', 6379);
        $this->container->setParameter('cache.redis.password', '');
        $this->container->setParameter('cache.redis.database', 0);
        $this->container->setParameter('cache.redis.timeout', 2.0);
        $this->container->setParameter('cache.redis.prefix', 'btf_');

        $this->pass->process($this->container);

        $definition = $this->container->getDefinition(RedisHealthCheck::class);
        $this->assertTrue($definition->hasTag('wordpress.health_check'));
    }

    public function testUnknownStrategyFallsToTransient(): void
    {
        $this->container->setParameter('cache.strategy', 'unknown');
        $this->container->setDefinition(TransientCache::class, new Definition(TransientCache::class));

        $this->pass->process($this->container);

        $this->assertSame(TransientCache::class, (string) $this->container->getAlias(CacheInterface::class));
    }
}
