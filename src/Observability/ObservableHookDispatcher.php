<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Observability\Contracts\PerformanceCollectorInterface;

/**
 * Decorator that adds observability to the HookDispatcher.
 *
 * Logs hook registrations and collects performance metrics.
 * Wraps the real HookDispatcher without modifying it.
 */
final class ObservableHookDispatcher implements HookDispatcherInterface
{
    private readonly HookDispatcherInterface $inner;
    private readonly LoggerInterface $logger;
    private readonly PerformanceCollectorInterface $collector;

    public function __construct(
        HookDispatcherInterface $inner,
        LoggerInterface $logger,
        PerformanceCollectorInterface $collector
    ) {
        $this->inner = $inner;
        $this->logger = $logger;
        $this->collector = $collector;
    }

    public function addAction(string $hookName, callable|string $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        $this->logger->debug('Registering action: {hook}', [
            'hook' => $hookName,
            'priority' => $priority,
        ]);
        $this->collector->increment('hooks.actions_registered');
        $this->inner->addAction($hookName, $callback, $priority, $acceptedArgs);
    }

    public function addFilter(string $hookName, callable|string $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        $this->logger->debug('Registering filter: {hook}', [
            'hook' => $hookName,
            'priority' => $priority,
        ]);
        $this->collector->increment('hooks.filters_registered');
        $this->inner->addFilter($hookName, $callback, $priority, $acceptedArgs);
    }

    public function removeAction(string $hookName, callable|string $callback, int $priority = 10): void
    {
        $this->inner->removeAction($hookName, $callback, $priority);
    }

    public function removeFilter(string $hookName, callable|string $callback, int $priority = 10): void
    {
        $this->inner->removeFilter($hookName, $callback, $priority);
    }

    public function doAction(string $hookName, mixed ...$args): void
    {
        $this->inner->doAction($hookName, ...$args);
    }

    public function applyFilters(string $hookName, mixed $value, mixed ...$args): mixed
    {
        return $this->inner->applyFilters($hookName, $value, ...$args);
    }

    public function registerActivationHook(string $file, callable $callback): void
    {
        $this->logger->info('Registering activation hook for: {file}', ['file' => $file]);
        $this->inner->registerActivationHook($file, $callback);
    }

    public function registerDeactivationHook(string $file, callable $callback): void
    {
        $this->logger->info('Registering deactivation hook for: {file}', ['file' => $file]);
        $this->inner->registerDeactivationHook($file, $callback);
    }
}
