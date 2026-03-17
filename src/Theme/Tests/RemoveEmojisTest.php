<?php

declare(strict_types=1);

namespace BackTo\Framework\Theme\Tests;

require_once __DIR__ . '/wp_stubs.php';

use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Theme\Actions\RemoveEmojis;
use PHPUnit\Framework\TestCase;

class RemoveEmojisTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['_removed_actions'] = [];
        $GLOBALS['_removed_filters'] = [];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_removed_actions'], $GLOBALS['_removed_filters']);
    }

    public function testImplementsHooksInterface(): void
    {
        $this->assertInstanceOf(Hooks::class, new RemoveEmojis());
    }

    public function testRemovesEmojiActions(): void
    {
        $action = new RemoveEmojis();
        $action->hooks();

        $callbacks = array_column($GLOBALS['_removed_actions'], 'callback');

        $this->assertContains('wp_enqueue_emoji_styles', $callbacks);
        $this->assertContains('print_emoji_detection_script', $callbacks);
        $this->assertContains('print_emoji_styles', $callbacks);
    }

    public function testRemovesEmojiFilters(): void
    {
        $action = new RemoveEmojis();
        $action->hooks();

        $callbacks = array_column($GLOBALS['_removed_filters'], 'callback');

        $this->assertContains('wp_staticize_emoji', $callbacks);
        $this->assertContains('wp_staticize_emoji_for_email', $callbacks);
    }

    public function testRemovesEmojiStylesFromAllContexts(): void
    {
        $action = new RemoveEmojis();
        $action->hooks();

        // wp_enqueue_emoji_styles should be removed from admin, embed, and front
        $emojiStyleRemovals = array_filter(
            $GLOBALS['_removed_actions'],
            fn(array $r) => $r['callback'] === 'wp_enqueue_emoji_styles'
        );

        $tags = array_column($emojiStyleRemovals, 'tag');
        $this->assertContains('admin_enqueue_scripts', $tags);
        $this->assertContains('enqueue_embed_scripts', $tags);
        $this->assertContains('wp_enqueue_scripts', $tags);
    }

    public function testRemovesEmojiDetectionScriptFromWpHead(): void
    {
        $action = new RemoveEmojis();
        $action->hooks();

        $found = false;
        foreach ($GLOBALS['_removed_actions'] as $removed) {
            if ($removed['callback'] === 'print_emoji_detection_script' && $removed['tag'] === 'wp_head') {
                $found = true;
                $this->assertSame(7, $removed['priority']);
            }
        }
        $this->assertTrue($found, 'print_emoji_detection_script should be removed from wp_head at priority 7');
    }

    public function testRemovesEmailEmojiFilter(): void
    {
        $action = new RemoveEmojis();
        $action->hooks();

        $mailFilters = array_filter(
            $GLOBALS['_removed_filters'],
            fn(array $r) => $r['tag'] === 'wp_mail'
        );

        $this->assertNotEmpty($mailFilters, 'wp_mail emoji filter should be removed');
    }

    public function testTotalRemovalsCount(): void
    {
        $action = new RemoveEmojis();
        $action->hooks();

        // 8 remove_action + 3 remove_filter = 11 total
        $totalRemovals = count($GLOBALS['_removed_actions']) + count($GLOBALS['_removed_filters']);
        $this->assertSame(11, $totalRemovals);
    }
}
