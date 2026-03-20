<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

/**
 * Abstracts WordPress hook system (add_action / add_filter).
 *
 * Port interface — domain and application layers depend on this contract,
 * while the WordPress adapter provides the concrete implementation.
 */
interface HookDispatcherInterface
{
    public function addAction(string $hookName, callable|string $callback, int $priority = 10, int $acceptedArgs = 1): void;

    public function addFilter(string $hookName, callable|string $callback, int $priority = 10, int $acceptedArgs = 1): void;

    public function removeAction(string $hookName, callable|string $callback, int $priority = 10): void;

    public function removeFilter(string $hookName, callable|string $callback, int $priority = 10): void;

    /**
     * Execute an action hook.
     */
    public function doAction(string $hookName, mixed ...$args): void;

    /**
     * Apply a filter hook and return the result.
     *
     * @param mixed $value The value to filter.
     * @param mixed ...$args Additional arguments passed to callbacks.
     * @return mixed The filtered value.
     */
    public function applyFilters(string $hookName, mixed $value, mixed ...$args): mixed;

    /**
     * Register an activation hook for a plugin file.
     *
     * @param string $file
     * @param callable $callback
     */
    public function registerActivationHook(string $file, callable $callback): void;

    /**
     * Register a deactivation hook for a plugin file.
     *
     * @param string $file
     * @param callable $callback
     */
    public function registerDeactivationHook(string $file, callable $callback): void;
}
