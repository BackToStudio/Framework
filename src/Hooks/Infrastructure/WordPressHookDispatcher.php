<?php

declare(strict_types=1);

namespace BackTo\Framework\Hooks\Infrastructure;

use BackTo\Framework\Contracts\HookDispatcherInterface;

use function add_action;
use function add_filter;
use function is_admin;
use function register_activation_hook;
use function register_deactivation_hook;
use function remove_action;
use function remove_filter;

/**
 * WordPress adapter for the HookDispatcher port.
 */
final class WordPressHookDispatcher implements HookDispatcherInterface
{
    public function addAction(string $hookName, callable|string $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        add_action($hookName, $callback, $priority, $acceptedArgs);
    }

    public function addFilter(string $hookName, callable|string $callback, int $priority = 10, int $acceptedArgs = 1): void
    {
        add_filter($hookName, $callback, $priority, $acceptedArgs);
    }

    public function removeAction(string $hookName, callable|string $callback, int $priority = 10): void
    {
        remove_action($hookName, $callback, $priority);
    }

    public function removeFilter(string $hookName, callable|string $callback, int $priority = 10): void
    {
        remove_filter($hookName, $callback, $priority);
    }

    public function isAdmin(): bool
    {
        return is_admin();
    }

    public function registerActivationHook(string $file, callable $callback): void
    {
        register_activation_hook($file, $callback);
    }

    public function registerDeactivationHook(string $file, callable $callback): void
    {
        register_deactivation_hook($file, $callback);
    }
}
