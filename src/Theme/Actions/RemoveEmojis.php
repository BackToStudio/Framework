<?php

declare(strict_types=1);

namespace BackTo\Framework\Theme\Actions;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

final class RemoveEmojis implements Hooks
{
    private readonly HookDispatcherInterface $hookDispatcher;

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->removeAction('admin_enqueue_scripts', 'wp_enqueue_emoji_styles');
        $this->hookDispatcher->removeAction('enqueue_embed_scripts', 'wp_enqueue_emoji_styles');
        $this->hookDispatcher->removeAction('wp_enqueue_scripts', 'wp_enqueue_emoji_styles');
        $this->hookDispatcher->removeAction('admin_print_scripts', 'print_emoji_detection_script');
        $this->hookDispatcher->removeAction('admin_print_styles', 'print_emoji_styles');
        $this->hookDispatcher->removeAction('wp_head', 'print_emoji_detection_script', 7);
        $this->hookDispatcher->removeAction('wp_print_styles', 'print_emoji_styles');
        $this->hookDispatcher->removeFilter('the_content_feed', 'wp_staticize_emoji');
        $this->hookDispatcher->removeFilter('comment_text_rss', 'wp_staticize_emoji');
        $this->hookDispatcher->removeFilter('wp_mail', 'wp_staticize_emoji_for_email');
    }
}
