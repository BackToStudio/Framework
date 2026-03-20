<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

/**
 * Abstraction over WordPress site-level functions.
 *
 * Replaces direct calls to site_url(), home_url(), rest_get_url_prefix()
 * in domain code.
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
     * Get site information (equivalent to get_bloginfo()).
     */
    public function getBlogInfo(string $show): string;

    /**
     * Get a theme modification value (equivalent to get_theme_mod()).
     */
    public function getThemeMod(string $name, mixed $default = false): mixed;

    /**
     * Get an attachment image URL (equivalent to wp_get_attachment_image_url()).
     */
    public function getAttachmentImageUrl(int $attachmentId, string $size = 'thumbnail'): string|false;
}
