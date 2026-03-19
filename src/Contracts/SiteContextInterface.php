<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

/**
 * Abstraction over WordPress site-level functions.
 *
 * Replaces direct calls to site_url(), home_url(), rest_get_url_prefix(),
 * and apply_filters() in domain code.
 */
interface SiteContextInterface
{
    /**
     * Get the site URL (equivalent to site_url()).
     */
    public function getSiteUrl(): string;

    /**
     * Get the home URL (equivalent to home_url()).
     */
    public function getHomeUrl(): string;

    /**
     * Get the REST API URL prefix (equivalent to rest_get_url_prefix()).
     */
    public function getRestUrlPrefix(): string;

    /**
     * Apply a filter hook and return the result.
     *
     * @param mixed $value The value to filter.
     * @param mixed ...$args Additional arguments passed to callbacks.
     * @return mixed The filtered value.
     */
    public function applyFilters(string $hookName, mixed $value, mixed ...$args): mixed;
}
