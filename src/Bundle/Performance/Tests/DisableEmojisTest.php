<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Bundle\Performance\Hooks\Cleanup\DisableEmojis;
use PHPUnit\Framework\TestCase;

class DisableEmojisTest extends TestCase
{
    public function testHooksAreRegistered(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->exactly(4))
            ->method('removeAction');
        $dispatcher->expects($this->exactly(3))
            ->method('addFilter');

        $hook = new DisableEmojis($dispatcher);
        $hook->hooks();
    }

    public function testRemoveEmojiDnsPrefetch(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $hook = new DisableEmojis($dispatcher);

        $urls = ['https://svn.wordpress.org', 'https://fonts.googleapis.com', '//s.w.org'];

        $result = $hook->removeEmojiDnsPrefetch($urls, 'dns-prefetch');

        $this->assertSame(['https://fonts.googleapis.com'], $result);
    }

    public function testRemoveEmojiDnsPrefetchIgnoresOtherRelTypes(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $hook = new DisableEmojis($dispatcher);

        $urls = ['https://svn.wordpress.org'];

        $result = $hook->removeEmojiDnsPrefetch($urls, 'preconnect');

        $this->assertSame($urls, $result);
    }

    public function testRemoveTinyMceEmoji(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $hook = new DisableEmojis($dispatcher);

        $plugins = ['wplink', 'wpemoji', 'wpdialogs'];

        $result = $hook->removeTinyMceEmoji($plugins);

        $this->assertSame(['wplink', 'wpdialogs'], $result);
    }
}
