<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Theme\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Theme\Actions\CleanHead;
use PHPUnit\Framework\TestCase;

class CleanHeadTest extends TestCase
{
    private HookDispatcherInterface $hookDispatcher;
    private array $removedActions;

    protected function setUp(): void
    {
        $this->removedActions = [];
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->hookDispatcher->method('removeAction')
            ->willReturnCallback(function (string $tag, $callback, int $priority = 10) {
                $this->removedActions[] = ['tag' => $tag, 'callback' => $callback, 'priority' => $priority];
            });
    }

    public function testImplementsHooksInterface(): void
    {
        $this->assertInstanceOf(Hooks::class, new CleanHead($this->hookDispatcher));
    }

    public function testRemovesAllExpectedWpHeadActions(): void
    {
        $action = new CleanHead($this->hookDispatcher);
        $action->hooks();

        $tags = array_column($this->removedActions, 'tag');

        foreach ($tags as $tag) {
            $this->assertSame('wp_head', $tag);
        }
    }

    public function testRemovesExactly8Actions(): void
    {
        $action = new CleanHead($this->hookDispatcher);
        $action->hooks();

        $this->assertCount(8, $this->removedActions);
    }

    public function testRemovesFeedLinksExtra(): void
    {
        $action = new CleanHead($this->hookDispatcher);
        $action->hooks();

        $callbacks = array_column($this->removedActions, 'callback');
        $this->assertContains('feed_links_extra', $callbacks);
    }

    public function testRemovesFeedLinks(): void
    {
        $action = new CleanHead($this->hookDispatcher);
        $action->hooks();

        $callbacks = array_column($this->removedActions, 'callback');
        $this->assertContains('feed_links', $callbacks);
    }

    public function testRemovesRsdLink(): void
    {
        $action = new CleanHead($this->hookDispatcher);
        $action->hooks();

        $callbacks = array_column($this->removedActions, 'callback');
        $this->assertContains('rsd_link', $callbacks);
    }

    public function testRemovesWlwManifestLink(): void
    {
        $action = new CleanHead($this->hookDispatcher);
        $action->hooks();

        $callbacks = array_column($this->removedActions, 'callback');
        $this->assertContains('wlwmanifest_link', $callbacks);
    }

    public function testRespectsPriorityForFeedLinks(): void
    {
        $action = new CleanHead($this->hookDispatcher);
        $action->hooks();

        $feedLinksExtra = null;
        $feedLinks = null;
        foreach ($this->removedActions as $removed) {
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
        $action = new CleanHead($this->hookDispatcher);
        $action->hooks();
        $action->hooks();

        $this->assertCount(16, $this->removedActions);
    }
}
