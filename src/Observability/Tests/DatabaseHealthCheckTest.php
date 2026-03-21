<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Tests;

use BackTo\Framework\Contracts\HealthCheckInterface;
use BackTo\Framework\Contracts\HealthCheckResult;
use BackTo\Framework\Contracts\HealthCheckStatus;
use BackTo\Framework\Observability\HealthCheck\DatabaseConnectionInterface;
use BackTo\Framework\Observability\HealthCheck\DatabaseHealthCheck;
use PHPUnit\Framework\TestCase;

class DatabaseHealthCheckTest extends TestCase
{
    private DatabaseConnectionInterface $connection;
    private DatabaseHealthCheck $check;

    protected function setUp(): void
    {
        $this->connection = $this->createMock(DatabaseConnectionInterface::class);
        $this->check = new DatabaseHealthCheck($this->connection);
    }

    public function testImplementsHealthCheckInterface(): void
    {
        $this->assertInstanceOf(HealthCheckInterface::class, $this->check);
    }

    public function testGetName(): void
    {
        $this->assertSame('database', $this->check->getName());
    }

    public function testHealthyWhenDatabaseResponds(): void
    {
        $this->connection->method('query')->with('SELECT 1')->willReturn('1');
        $this->connection->method('getTableCount')->willReturn(12);
        $this->connection->method('getServerInfo')->willReturn('MySQL 8.0');

        $result = $this->check->check();

        $this->assertTrue($result->isHealthy());
        $this->assertSame('Database operational', $result->getMessage());
        $this->assertArrayHasKey('response_time_ms', $result->getMetadata());
        $this->assertSame(12, $result->getMetadata()['table_count']);
        $this->assertSame('MySQL 8.0', $result->getMetadata()['server_info']);
    }

    public function testUnhealthyWhenQueryReturnsUnexpectedResult(): void
    {
        $this->connection->method('query')->willReturn('0');
        $this->connection->method('getTableCount')->willReturn(12);
        $this->connection->method('getServerInfo')->willReturn('MySQL 8.0');

        $result = $this->check->check();

        $this->assertSame(HealthCheckStatus::Unhealthy, $result->getHealthCheckStatus());
        $this->assertStringContainsString('unexpected result', $result->getMessage());
    }

    public function testUnhealthyWhenQueryReturnsNull(): void
    {
        $this->connection->method('query')->willReturn(null);

        $result = $this->check->check();

        $this->assertSame(HealthCheckStatus::Unhealthy, $result->getHealthCheckStatus());
    }

    public function testUnhealthyWhenNoTablesFound(): void
    {
        $this->connection->method('query')->willReturn('1');
        $this->connection->method('getTableCount')->willReturn(0);

        $result = $this->check->check();

        $this->assertSame(HealthCheckStatus::Unhealthy, $result->getHealthCheckStatus());
        $this->assertStringContainsString('No WordPress tables', $result->getMessage());
    }

    public function testUnhealthyOnException(): void
    {
        $this->connection->method('query')->willThrowException(
            new \RuntimeException('Connection refused'),
        );

        $result = $this->check->check();

        $this->assertSame(HealthCheckStatus::Unhealthy, $result->getHealthCheckStatus());
        $this->assertStringContainsString('Connection refused', $result->getMessage());
    }

    public function testMetadataIncludesResponseTime(): void
    {
        $this->connection->method('query')->willReturn('1');
        $this->connection->method('getTableCount')->willReturn(5);
        $this->connection->method('getServerInfo')->willReturn('MariaDB 10.6');

        $result = $this->check->check();
        $metadata = $result->getMetadata();

        $this->assertIsFloat($metadata['response_time_ms']);
        $this->assertGreaterThanOrEqual(0, $metadata['response_time_ms']);
    }
}
