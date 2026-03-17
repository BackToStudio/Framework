<?php

/**
 * WordPress function stubs for unit testing outside of WordPress.
 * These are only defined if the real functions don't exist.
 */

if (!\function_exists('wp_next_scheduled')) {
    function wp_next_scheduled(string $hook): bool
    {
        return false;
    }
}

if (!\function_exists('wp_schedule_event')) {
    function wp_schedule_event(int $timestamp, string $recurrence, string $hook): void
    {
    }
}

if (!\function_exists('wp_clear_scheduled_hook')) {
    function wp_clear_scheduled_hook(string $hook): void
    {
    }
}

if (!\function_exists('get_transient')) {
    function get_transient(string $key): mixed
    {
        return false;
    }
}

if (!\function_exists('set_transient')) {
    function set_transient(string $key, mixed $value, int $expiration = 0): bool
    {
        return true;
    }
}

if (!\function_exists('delete_transient')) {
    function delete_transient(string $key): bool
    {
        return true;
    }
}
