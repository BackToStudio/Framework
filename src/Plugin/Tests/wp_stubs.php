<?php

declare(strict_types=1);

/**
 * WordPress function stubs for Plugin module tests.
 */

if (!function_exists('load_plugin_textdomain')) {
    function load_plugin_textdomain(string $domain, string|false $deprecated, string $path): bool
    {
        $GLOBALS['_load_plugin_textdomain_calls'][] = [
            'domain' => $domain,
            'deprecated' => $deprecated,
            'path' => $path,
        ];
        return true;
    }
}

if (!function_exists('load_muplugin_textdomain')) {
    function load_muplugin_textdomain(string $domain, string $path): bool
    {
        $GLOBALS['_load_muplugin_textdomain_calls'][] = [
            'domain' => $domain,
            'path' => $path,
        ];
        return true;
    }
}
