<?php

namespace BackTo\Framework\Contracts;

/**
 * Abstracts WordPress hook system (add_action / add_filter).
 *
 * Port interface — domain and application layers depend on this contract,
 * while the WordPress adapter provides the concrete implementation.
 */
interface HookDispatcherInterface
{
    /**
     * @param string $hookName
     * @param callable $callback
     * @param int $priority
     * @param int $acceptedArgs
     */
    public function addAction(string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1): void;

    /**
     * @param string $hookName
     * @param callable $callback
     * @param int $priority
     * @param int $acceptedArgs
     */
    public function addFilter(string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1): void;

    /**
     * @param string $hookName
     * @param callable $callback
     * @param int $priority
     */
    public function removeAction(string $hookName, callable $callback, int $priority = 10): void;

    /**
     * Check if the current request is for an admin page.
     */
    public function isAdmin(): bool;

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
