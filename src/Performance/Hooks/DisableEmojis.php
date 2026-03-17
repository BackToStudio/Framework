<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Hooks;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

/**
 * Disable WordPress emoji scripts and styles.
 *
 * Removes ~30 KB of inline JS/CSS that most sites do not need.
 */
final class DisableEmojis implements Hooks
{
    private readonly HookDispatcherInterface $hookDispatcher;

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->removeAction('wp_head', 'print_emoji_detection_script', 7);
        $this->hookDispatcher->removeAction('admin_print_scripts', 'print_emoji_detection_script');
        $this->hookDispatcher->removeAction('wp_print_styles', 'print_emoji_styles');
        $this->hookDispatcher->removeAction('admin_print_styles', 'print_emoji_styles');
        $this->hookDispatcher->addFilter('wp_resource_hints', [$this, 'removeEmojiDnsPrefetch'], 10, 2);
        $this->hookDispatcher->addFilter('tiny_mce_plugins', [$this, 'removeTinyMceEmoji']);
        $this->hookDispatcher->addFilter('emoji_svg_url', '__return_false');
    }

    /**
     * Remove emoji CDN from DNS prefetch hints.
     *
     * @param string[] $urls
     * @return string[]
     */
    public function removeEmojiDnsPrefetch(array $urls, string $relationType): array
    {
        if ($relationType !== 'dns-prefetch') {
            return $urls;
        }

        return array_values(array_filter($urls, function (string $url): bool {
            return !str_contains($url, 'svn.wordpress.org')
                && !str_contains($url, 's.w.org');
        }));
    }

    /**
     * Remove wpemoji from TinyMCE plugins.
     *
     * @param string[] $plugins
     * @return string[]
     */
    public function removeTinyMceEmoji(array $plugins): array
    {
        return array_values(array_diff($plugins, ['wpemoji']));
    }
}
