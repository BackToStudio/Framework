<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Observability\Contracts\PerformanceCollectorInterface;
use BackTo\Framework\Observability\ObservableHookDispatcher;
use PHPUnit\Framework\TestCase;

class ObservableHookDispatcherTest extends TestCase
{
    private HookDispatcherInterface $inner;
    private LoggerInterface $logger;
    private PerformanceCollectorInterface $collector;
    private ObservableHookDispatcher $dispatcher;

    protected function setUp(): void
    {
        $this->inner = $this->createMock(HookDispatcherInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->collector = $this->createMock(PerformanceCollectorInterface::class);
        $this->dispatcher = new ObservableHookDispatcher($this->inner, $this->logger, $this->collector);
    }

    public function testAddActionDelegatesToInner(): void
    {
        $callback = static fn () => null;

        $this->inner->expects($this->once())
            ->method('addAction')
            ->with('init', $callback, 10, 1);

        $this->dispatcher->addAction('init', $callback);
    }

    public function testAddActionLogsAndIncrementsCounter(): void
    {
        $this->logger->expects($this->once())
            ->method('debug')
            ->with('Registering action: {hook}', $this->callback(static fn ($ctx) => $ctx['hook'] === 'init'));

        $this->collector->expects($this->once())
            ->method('increment')
            ->with('hooks.actions_registered');

        $this->dispatcher->addAction('init', static fn () => null);
    }

    public function testAddFilterLogsAndIncrementsCounter(): void
    {
        $this->logger->expects($this->once())
            ->method('debug');

        $this->collector->expects($this->once())
            ->method('increment')
            ->with('hooks.filters_registered');

        $this->inner->expects($this->once())
            ->method('addFilter')
            ->with('the_title', $this->anything(), 10, 1);

        $this->dispatcher->addFilter('the_title', static fn ($t) => $t);
    }

    public function testApplyFiltersDelegatesToInner(): void
    {
        $this->inner->expects($this->once())
            ->method('applyFilters')
            ->with('my_filter', 'value', 'arg1')
            ->willReturn('filtered');

        $this->assertSame('filtered', $this->dispatcher->applyFilters('my_filter', 'value', 'arg1'));
    }

    public function testRegisterActivationHookLogs(): void
    {
        $this->logger->expects($this->once())
            ->method('info');

        $this->inner->expects($this->once())
            ->method('registerActivationHook');

        $this->dispatcher->registerActivationHook('/path/plugin.php', static fn () => null);
    }
}
