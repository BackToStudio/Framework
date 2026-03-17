<?php

declare(strict_types=1);

/**
 * WordPress function stubs for Theme module tests.
 *
 * Note: remove_action/remove_filter may already be defined by Hooks/Tests/wp_stubs.php
 * when running the full test suite. We use the same storage format ($GLOBALS['_removed_actions'])
 * so tests work regardless of load order.
 */

if (!function_exists('remove_action')) {
    function remove_action(string $tag, callable|string $callback, int $priority = 10): bool
    {
        $GLOBALS['_removed_actions'][] = compact('tag', 'callback', 'priority');
        return true;
    }
}

if (!function_exists('remove_filter')) {
    function remove_filter(string $tag, callable|string $callback, int $priority = 10): bool
    {
        $GLOBALS['_removed_filters'][] = compact('tag', 'callback', 'priority');
        return true;
    }
}

if (!function_exists('load_theme_textdomain')) {
    function load_theme_textdomain(string $domain, string $path): bool
    {
        $GLOBALS['_load_theme_textdomain_calls'][] = [
            'domain' => $domain,
            'path' => $path,
        ];
        return true;
    }
}
