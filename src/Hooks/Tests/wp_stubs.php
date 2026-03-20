<?php

declare(strict_types=1);

/**
 * WordPress function stubs for Hooks module tests.
 */

if (!function_exists('add_action')) {
    function add_action(string $tag, callable|string $callback, int $priority = 10, int $acceptedArgs = 1): bool
    {
        $GLOBALS['_wp_hooks']['actions'][] = compact('tag', 'callback', 'priority', 'acceptedArgs');
        return true;
    }
}

if (!function_exists('add_filter')) {
    function add_filter(string $tag, callable|string $callback, int $priority = 10, int $acceptedArgs = 1): bool
    {
        $GLOBALS['_wp_hooks']['filters'][] = compact('tag', 'callback', 'priority', 'acceptedArgs');
        return true;
    }
}

if (!function_exists('remove_action')) {
    function remove_action(string $tag, callable|string $callback, int $priority = 10): bool
    {
        $GLOBALS['_wp_hooks']['removed_actions'][] = compact('tag', 'callback', 'priority');
        $GLOBALS['_removed_actions'][] = compact('tag', 'callback', 'priority');
        return true;
    }
}

if (!function_exists('remove_filter')) {
    function remove_filter(string $tag, callable|string $callback, int $priority = 10): bool
    {
        $GLOBALS['_wp_hooks']['removed_filters'][] = compact('tag', 'callback', 'priority');
        $GLOBALS['_removed_filters'][] = compact('tag', 'callback', 'priority');
        return true;
    }
}

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        return $GLOBALS['_wp_is_admin'] ?? false;
    }
}

if (!function_exists('do_action')) {
    function do_action(string $hookName, mixed ...$args): void
    {
        $GLOBALS['_wp_hooks']['do_actions'][] = ['hook' => $hookName, 'args' => $args];
    }
}

if (!function_exists('apply_filters')) {
    function apply_filters(string $hookName, mixed $value, mixed ...$args): mixed
    {
        $GLOBALS['_wp_hooks']['apply_filters'][] = ['hook' => $hookName, 'value' => $value, 'args' => $args];
        return $value;
    }
}

if (!function_exists('register_activation_hook')) {
    function register_activation_hook(string $file, callable $callback): void
    {
        $GLOBALS['_wp_hooks']['activation'][] = compact('file', 'callback');
    }
}

if (!function_exists('register_deactivation_hook')) {
    function register_deactivation_hook(string $file, callable $callback): void
    {
        $GLOBALS['_wp_hooks']['deactivation'][] = compact('file', 'callback');
    }
}
