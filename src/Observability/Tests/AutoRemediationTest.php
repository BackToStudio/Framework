<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Tests;

use BackTo\Framework\Contracts\HealthCheckResult;
use BackTo\Framework\Contracts\LoggerInterface;
use BackTo\Framework\Observability\AutoRemediation;
use BackTo\Framework\Observability\Contracts\AlertDispatcherInterface;
use BackTo\Framework\Observability\Contracts\MetricStoreInterface;
use BackTo\Framework\Observability\Contracts\RemediationInterface;
use PHPUnit\Framework\TestCase;

final class AutoRemediationTest extends TestCase
{
    private AutoRemediation $remediation;
    private LoggerInterface $logger;
    private AlertDispatcherInterface $alertDispatcher;
    private MetricStoreInterface $metricStore;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->alertDispatcher = $this->createMock(AlertDispatcherInterface::class);
        $this->metricStore = $this->createMock(MetricStoreInterface::class);
        $this->remediation = new AutoRemediation($this->logger, $this->alertDispatcher, $this->metricStore);
    }

    public function test_skips_healthy_results(): void
    {
        $handler = $this->createMock(RemediationInterface::class);
        $handler->method('getTargetCheck')->willReturn('cache');
        $handler->expects($this->never())->method('remediate');

        $this->remediation->addHandler($handler);
        $this->remediation->process(['cache' => HealthCheckResult::healthy('OK')]);
    }

    public function test_triggers_remediation_on_degraded(): void
    {
        $handler = $this->createMock(RemediationInterface::class);
        $handler->method('getTargetCheck')->willReturn('queue');
        $handler->method('getDescription')->willReturn('Rescue stuck jobs');
        $handler->expects($this->once())
            ->method('remediate')
            ->willReturn(true);

        $this->alertDispatcher->expects($this->once())
            ->method('info')
            ->with($this->stringContains('succeeded'));

        $this->remediation->addHandler($handler);
        $this->remediation->process(['queue' => HealthCheckResult::degraded('High queue depth')]);
    }

    public function test_triggers_remediation_on_unhealthy(): void
    {
        $handler = $this->createMock(RemediationInterface::class);
        $handler->method('getTargetCheck')->willReturn('cache');
        $handler->method('getDescription')->willReturn('Clear cache');
        $handler->expects($this->once())
            ->method('remediate')
            ->willReturn(true);

        $this->remediation->addHandler($handler);
        $this->remediation->process(['cache' => HealthCheckResult::unhealthy('Cache down')]);
    }

    public function test_alerts_warning_on_failed_remediation(): void
    {
        $handler = $this->createMock(RemediationInterface::class);
        $handler->method('getTargetCheck')->willReturn('cache');
        $handler->method('getDescription')->willReturn('Clear cache');
        $handler->method('remediate')->willReturn(false);

        $this->alertDispatcher->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('failed'));

        $this->metricStore->expects($this->exactly(2))
            ->method('increment');

        $this->remediation->addHandler($handler);
        $this->remediation->process(['cache' => HealthCheckResult::unhealthy('Cache down')]);
    }

    public function test_logs_error_on_exception(): void
    {
        $handler = $this->createMock(RemediationInterface::class);
        $handler->method('getTargetCheck')->willReturn('cache');
        $handler->method('getDescription')->willReturn('Clear cache');
        $handler->method('remediate')->willThrowException(new \RuntimeException('fail'));

        $this->logger->expects($this->atLeastOnce())
            ->method('error')
            ->with($this->stringContains('fail'));

        $this->remediation->addHandler($handler);
        $this->remediation->process(['cache' => HealthCheckResult::unhealthy('Cache down')]);
    }

    public function test_skips_unhandled_checks(): void
    {
        $handler = $this->createMock(RemediationInterface::class);
        $handler->method('getTargetCheck')->willReturn('queue');
        $handler->expects($this->never())->method('remediate');

        $this->remediation->addHandler($handler);
        $this->remediation->process(['cache' => HealthCheckResult::unhealthy('Cache down')]);
    }

    public function test_records_metrics_on_success(): void
    {
        $handler = $this->createMock(RemediationInterface::class);
        $handler->method('getTargetCheck')->willReturn('cache');
        $handler->method('getDescription')->willReturn('Clear cache');
        $handler->method('remediate')->willReturn(true);

        // increment called for: remediation.attempts + remediation.successes
        $this->metricStore->expects($this->exactly(2))
            ->method('increment');

        $this->remediation->addHandler($handler);
        $this->remediation->process(['cache' => HealthCheckResult::degraded('Cache slow')]);
    }

    public function test_get_handlers_returns_registered_handlers(): void
    {
        $handler = $this->createMock(RemediationInterface::class);
        $handler->method('getTargetCheck')->willReturn('cache');

        $this->remediation->addHandler($handler);

        $this->assertCount(1, $this->remediation->getHandlers());
        $this->assertSame($handler, $this->remediation->getHandlers()['cache']);
    }
}
