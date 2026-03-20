<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Tests;

use BackTo\Framework\Contracts\HealthCheckInterface;
use BackTo\Framework\Contracts\HealthCheckResult;
use BackTo\Framework\Observability\HealthCheckRegistry;
use PHPUnit\Framework\TestCase;

class HealthCheckRegistryTest extends TestCase
{
    public function testRunAllReturnsResults(): void
    {
        $registry = new HealthCheckRegistry();

        $check = $this->createMock(HealthCheckInterface::class);
        $check->method('getName')->willReturn('test');
        $check->method('check')->willReturn(HealthCheckResult::healthy('OK'));

        $registry->add($check);
        $results = $registry->runAll();

        $this->assertArrayHasKey('test', $results);
        $this->assertTrue($results['test']->isHealthy());
    }

    public function testIsHealthyReturnsFalseWhenOneCheckFails(): void
    {
        $registry = new HealthCheckRegistry();

        $healthy = $this->createMock(HealthCheckInterface::class);
        $healthy->method('getName')->willReturn('good');
        $healthy->method('check')->willReturn(HealthCheckResult::healthy());

        $unhealthy = $this->createMock(HealthCheckInterface::class);
        $unhealthy->method('getName')->willReturn('bad');
        $unhealthy->method('check')->willReturn(HealthCheckResult::unhealthy('down'));

        $registry->add($healthy);
        $registry->add($unhealthy);

        $this->assertFalse($registry->isHealthy());
    }

    public function testHandlesExceptionInHealthCheck(): void
    {
        $registry = new HealthCheckRegistry();

        $failing = $this->createMock(HealthCheckInterface::class);
        $failing->method('getName')->willReturn('broken');
        $failing->method('check')->willThrowException(new \RuntimeException('crash'));

        $registry->add($failing);
        $results = $registry->runAll();

        $this->assertFalse($results['broken']->isHealthy());
        $this->assertSame('crash', $results['broken']->getMessage());
    }

    public function testToArrayReturnsStructuredOutput(): void
    {
        $registry = new HealthCheckRegistry();

        $check = $this->createMock(HealthCheckInterface::class);
        $check->method('getName')->willReturn('db');
        $check->method('check')->willReturn(HealthCheckResult::degraded('slow', ['latency_ms' => 500]));

        $registry->add($check);
        $output = $registry->toArray();

        $this->assertSame('degraded', $output['db']['status']);
        $this->assertSame('slow', $output['db']['message']);
        $this->assertSame(500, $output['db']['metadata']['latency_ms']);
    }

    public function testEmptyRegistryIsHealthy(): void
    {
        $registry = new HealthCheckRegistry();

        $this->assertTrue($registry->isHealthy());
    }
}
