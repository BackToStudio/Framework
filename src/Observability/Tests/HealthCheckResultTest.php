<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Tests;

use BackTo\Framework\Contracts\HealthCheckResult;
use PHPUnit\Framework\TestCase;

class HealthCheckResultTest extends TestCase
{
    public function testHealthyResult(): void
    {
        $result = HealthCheckResult::healthy('all good', ['uptime' => 3600]);

        $this->assertTrue($result->isHealthy());
        $this->assertSame('healthy', $result->getStatus());
        $this->assertSame('all good', $result->getMessage());
        $this->assertSame(3600, $result->getMetadata()['uptime']);
    }

    public function testDegradedResult(): void
    {
        $result = HealthCheckResult::degraded('slow');

        $this->assertFalse($result->isHealthy());
        $this->assertSame('degraded', $result->getStatus());
    }

    public function testUnhealthyResult(): void
    {
        $result = HealthCheckResult::unhealthy('down');

        $this->assertFalse($result->isHealthy());
        $this->assertSame('unhealthy', $result->getStatus());
    }

    public function testToArray(): void
    {
        $result = HealthCheckResult::healthy('ok', ['key' => 'value']);
        $array = $result->toArray();

        $this->assertSame('healthy', $array['status']);
        $this->assertSame('ok', $array['message']);
        $this->assertSame(['key' => 'value'], $array['metadata']);
    }
}
