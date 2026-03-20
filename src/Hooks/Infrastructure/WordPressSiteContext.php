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

    public function getBlogInfo(string $show): string
    {
        return function_exists('get_bloginfo') ? \get_bloginfo($show) : '';
    }

    public function getThemeMod(string $name, mixed $default = false): mixed
    {
        return function_exists('get_theme_mod') ? \get_theme_mod($name, $default) : $default;
    }

    public function getAttachmentImageUrl(int $attachmentId, string $size = 'thumbnail'): string|false
    {
        return function_exists('wp_get_attachment_image_url')
            ? \wp_get_attachment_image_url($attachmentId, $size)
            : false;
    }
}
