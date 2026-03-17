<?php

declare(strict_types=1);

/**
 * WordPress function stubs for Options module tests.
 */

if (!function_exists('get_option')) {
    function get_option(string $key, mixed $default = false): mixed
    {
        $store = $GLOBALS['_wp_options'] ?? [];
        if (!array_key_exists($key, $store)) {
            return $default;
        }
        return $store[$key];
    }
}

if (!function_exists('update_option')) {
    function update_option(string $key, mixed $value): bool
    {
        $GLOBALS['_wp_options'][$key] = $value;
        return true;
    }
}

if (!function_exists('delete_option')) {
    function delete_option(string $key): bool
    {
        if (!isset($GLOBALS['_wp_options'][$key])) {
            return false;
        }
        unset($GLOBALS['_wp_options'][$key]);
        return true;
    }
}
