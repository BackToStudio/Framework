<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Tests;

use BackTo\Framework\Cache\Contracts\RedisClientInterface;
use BackTo\Framework\Cache\HealthCheck\RedisHealthCheck;
use BackTo\Framework\Contracts\HealthCheckInterface;
use BackTo\Framework\Contracts\HealthCheckStatus;
use PHPUnit\Framework\TestCase;

class RedisHealthCheckTest extends TestCase
{
    private RedisClientInterface $client;
    private RedisHealthCheck $check;

    protected function setUp(): void
    {
        $this->client = $this->createMock(RedisClientInterface::class);
        $this->check = new RedisHealthCheck($this->client);
    }

    public function testImplementsHealthCheckInterface(): void
    {
        $this->assertInstanceOf(HealthCheckInterface::class, $this->check);
    }

    public function testGetName(): void
    {
        $this->assertSame('redis', $this->check->getName());
    }

    public function testHealthyWhenPingSucceeds(): void
    {
        $this->client->method('ping')->willReturn(true);
        $this->client->method('info')->willReturn([
            'redis_version' => '7.2.4',
            'connected_clients' => '3',
            'used_memory_human' => '1.5M',
            'uptime_in_seconds' => '86400',
        ]);

        $result = $this->check->check();

        $this->assertTrue($result->isHealthy());
        $this->assertSame('Redis operational', $result->getMessage());
        $this->assertSame('7.2.4', $result->getMetadata()['redis_version']);
    }

    public function testUnhealthyWhenPingFails(): void
    {
        $this->client->method('ping')->willReturn(false);

        $result = $this->check->check();

        $this->assertSame(HealthCheckStatus::Unhealthy, $result->getHealthCheckStatus());
        $this->assertStringContainsString('not responding', $result->getMessage());
    }

    public function testUnhealthyOnException(): void
    {
        $this->client->method('ping')->willThrowException(
            new \RuntimeException('Connection refused'),
        );

        $result = $this->check->check();

        $this->assertSame(HealthCheckStatus::Unhealthy, $result->getHealthCheckStatus());
        $this->assertStringContainsString('Connection refused', $result->getMessage());
    }

    public function testDegradedWhenMemoryHigh(): void
    {
        $this->client->method('ping')->willReturn(true);
        $this->client->method('info')->willReturn([
            'redis_version' => '7.2.4',
            'connected_clients' => '3',
            'used_memory_human' => '900M',
            'uptime_in_seconds' => '86400',
            'used_memory_peak' => '950000000',
            'maxmemory' => '1000000000',
        ]);

        $result = $this->check->check();

        $this->assertSame(HealthCheckStatus::Degraded, $result->getHealthCheckStatus());
        $this->assertStringContainsString('memory usage high', $result->getMessage());
    }

    public function testHealthyWhenMemoryBelowThreshold(): void
    {
        $this->client->method('ping')->willReturn(true);
        $this->client->method('info')->willReturn([
            'redis_version' => '7.2.4',
            'connected_clients' => '3',
            'used_memory_human' => '100M',
            'uptime_in_seconds' => '86400',
            'used_memory_peak' => '100000000',
            'maxmemory' => '1000000000',
        ]);

        $result = $this->check->check();

        $this->assertTrue($result->isHealthy());
    }

    public function testHealthyWhenNoMaxMemorySet(): void
    {
        $this->client->method('ping')->willReturn(true);
        $this->client->method('info')->willReturn([
            'redis_version' => '7.2.4',
            'connected_clients' => '1',
            'used_memory_human' => '2M',
            'uptime_in_seconds' => '3600',
            'maxmemory' => '0',
        ]);

        $result = $this->check->check();

        $this->assertTrue($result->isHealthy());
    }
}
