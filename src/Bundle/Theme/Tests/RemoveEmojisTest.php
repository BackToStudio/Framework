<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Theme\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Theme\Actions\RemoveEmojis;
use PHPUnit\Framework\TestCase;

class RemoveEmojisTest extends TestCase
{
    private HookDispatcherInterface $hookDispatcher;
    private array $removedActions;
    private array $removedFilters;

    protected function setUp(): void
    {
        $this->removedActions = [];
        $this->removedFilters = [];
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->hookDispatcher->method('removeAction')
            ->willReturnCallback(function (string $tag, $callback, int $priority = 10) {
                $this->removedActions[] = ['tag' => $tag, 'callback' => $callback, 'priority' => $priority];
            });
        $this->hookDispatcher->method('removeFilter')
            ->willReturnCallback(function (string $tag, $callback, int $priority = 10) {
                $this->removedFilters[] = ['tag' => $tag, 'callback' => $callback, 'priority' => $priority];
            });
    }

    public function testImplementsHooksInterface(): void
    {
        $this->assertInstanceOf(Hooks::class, new RemoveEmojis($this->hookDispatcher));
    }

    public function testRemovesEmojiActions(): void
    {
        $action = new RemoveEmojis($this->hookDispatcher);
        $action->hooks();

        $callbacks = array_column($this->removedActions, 'callback');

        $this->assertContains('wp_enqueue_emoji_styles', $callbacks);
        $this->assertContains('print_emoji_detection_script', $callbacks);
        $this->assertContains('print_emoji_styles', $callbacks);
    }

    public function testRemovesEmojiFilters(): void
    {
        $action = new RemoveEmojis($this->hookDispatcher);
        $action->hooks();

        $callbacks = array_column($this->removedFilters, 'callback');

        $this->assertContains('wp_staticize_emoji', $callbacks);
        $this->assertContains('wp_staticize_emoji_for_email', $callbacks);
    }

    public function testRemovesEmojiStylesFromAllContexts(): void
    {
        $action = new RemoveEmojis($this->hookDispatcher);
        $action->hooks();

        $emojiStyleRemovals = array_filter(
            $this->removedActions,
            fn(array $r) => $r['callback'] === 'wp_enqueue_emoji_styles'
        );

        $tags = array_column($emojiStyleRemovals, 'tag');
        $this->assertContains('admin_enqueue_scripts', $tags);
        $this->assertContains('enqueue_embed_scripts', $tags);
        $this->assertContains('wp_enqueue_scripts', $tags);
    }

    public function testRemovesEmojiDetectionScriptFromWpHead(): void
    {
        $action = new RemoveEmojis($this->hookDispatcher);
        $action->hooks();

        $found = false;
        foreach ($this->removedActions as $removed) {
            if ($removed['callback'] === 'print_emoji_detection_script' && $removed['tag'] === 'wp_head') {
                $found = true;
                $this->assertSame(7, $removed['priority']);
            }
        }
        $this->assertTrue($found, 'print_emoji_detection_script should be removed from wp_head at priority 7');
    }

    public function testRemovesEmailEmojiFilter(): void
    {
        $action = new RemoveEmojis($this->hookDispatcher);
        $action->hooks();

        $mailFilters = array_filter(
            $this->removedFilters,
            fn(array $r) => $r['tag'] === 'wp_mail'
        );

        $this->assertNotEmpty($mailFilters, 'wp_mail emoji filter should be removed');
    }

    public function testTotalRemovalsCount(): void
    {
        $action = new RemoveEmojis($this->hookDispatcher);
        $action->hooks();

        // 7 remove_action + 3 remove_filter = 10 total (removed duplicate print_emoji_styles)
        $totalRemovals = count($this->removedActions) + count($this->removedFilters);
        $this->assertSame(10, $totalRemovals);
    }
}
