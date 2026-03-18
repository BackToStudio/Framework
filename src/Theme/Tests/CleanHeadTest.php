<?php

declare(strict_types=1);

namespace BackTo\Framework\Theme\Tests;

require_once __DIR__ . '/wp_stubs.php';

use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Theme\Actions\CleanHead;
use PHPUnit\Framework\TestCase;

class CleanHeadTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['_removed_actions'] = [];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_removed_actions']);
    }

    public function testImplementsHooksInterface(): void
    {
        $this->assertInstanceOf(Hooks::class, new CleanHead());
    }

    public function testRemovesAllExpectedWpHeadActions(): void
    {
        $action = new CleanHead();
        $action->hooks();

        $tags = array_column($GLOBALS['_removed_actions'], 'tag');

        // All removals target wp_head
        foreach ($tags as $tag) {
            $this->assertSame('wp_head', $tag);
        }
    }

    public function testRemovesExactly8Actions(): void
    {
        $action = new CleanHead();
        $action->hooks();

        $this->assertCount(8, $GLOBALS['_removed_actions']);
    }

    public function testRemovesFeedLinksExtra(): void
    {
        $action = new CleanHead();
        $action->hooks();

        $callbacks = array_column($GLOBALS['_removed_actions'], 'callback');
        $this->assertContains('feed_links_extra', $callbacks);
    }

    public function testRemovesFeedLinks(): void
    {
        $action = new CleanHead();
        $action->hooks();

        $callbacks = array_column($GLOBALS['_removed_actions'], 'callback');
        $this->assertContains('feed_links', $callbacks);
    }

    public function testRemovesRsdLink(): void
    {
        $action = new CleanHead();
        $action->hooks();

        $callbacks = array_column($GLOBALS['_removed_actions'], 'callback');
        $this->assertContains('rsd_link', $callbacks);
    }

    public function testRemovesWlwManifestLink(): void
    {
        $action = new CleanHead();
        $action->hooks();

        $callbacks = array_column($GLOBALS['_removed_actions'], 'callback');
        $this->assertContains('wlwmanifest_link', $callbacks);
    }

    public function testRespectsPriorityForFeedLinks(): void
    {
        $action = new CleanHead();
        $action->hooks();

        // feed_links_extra has priority 3, feed_links has priority 2
        $feedLinksExtra = null;
        $feedLinks = null;
        foreach ($GLOBALS['_removed_actions'] as $removed) {
            if ($removed['callback'] === 'feed_links_extra') {
                $feedLinksExtra = $removed;
            }
            if ($removed['callback'] === 'feed_links') {
                $feedLinks = $removed;
            }
        }

        $this->assertNotNull($feedLinksExtra, 'feed_links_extra should be removed');
        $this->assertNotNull($feedLinks, 'feed_links should be removed');
        $this->assertSame(3, $feedLinksExtra['priority']);
        $this->assertSame(2, $feedLinks['priority']);
    }

    public function testCanBeCalledMultipleTimesWithoutError(): void
    {
        $action = new CleanHead();
        $action->hooks();
        $action->hooks();

        // Should have 16 removals (2x8), no errors
        $this->assertCount(16, $GLOBALS['_removed_actions']);
    }
}
