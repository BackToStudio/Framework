<?php

declare(strict_types=1);

namespace BackTo\Framework\Hooks\Infrastructure;

use BackTo\Framework\Contracts\SiteContextInterface;

/**
 * WordPress adapter for site-level context operations.
 */
final class WordPressSiteContext implements SiteContextInterface
{
    public function getSiteUrl(): string
    {
        return function_exists('site_url') ? \site_url() : '';
    }

    public function getHomeUrl(): string
    {
        return function_exists('home_url') ? \home_url() : '';
    }

    public function getRestUrlPrefix(): string
    {
        return function_exists('rest_get_url_prefix') ? rest_get_url_prefix() : 'wp-json';
    }

    public function applyFilters(string $hookName, mixed $value, mixed ...$args): mixed
    {
        if (function_exists('apply_filters')) {
            return \apply_filters($hookName, $value, ...$args);
        }

        return $value;
    }
}
