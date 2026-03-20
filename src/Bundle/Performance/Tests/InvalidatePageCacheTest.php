<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Tests;

use BackTo\Framework\Contracts\ContentQueryInterface;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Bundle\Performance\Contracts\PageCacheInterface;
use BackTo\Framework\Bundle\Performance\Hooks\InvalidatePageCache;
use PHPUnit\Framework\TestCase;

class InvalidatePageCacheTest extends TestCase
{
    private HookDispatcherInterface $hookDispatcher;
    private PageCacheInterface $pageCache;
    private ContentQueryInterface $contentQuery;
    private InvalidatePageCache $invalidator;

    protected function setUp(): void
    {
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->pageCache = $this->createMock(PageCacheInterface::class);
        $this->contentQuery = $this->createMock(ContentQueryInterface::class);
        $this->invalidator = new InvalidatePageCache(
            $this->hookDispatcher,
            $this->pageCache,
            $this->contentQuery,
        );
    }

    public function testHooksRegistersAllActions(): void
    {
        $this->hookDispatcher->expects($this->exactly(7))
            ->method('addAction')
            ->willReturnCallback(function (string $hook) {
                $expected = [
                    'save_post',
                    'deleted_post',
                    'transition_post_status',
                    'comment_post',
                    'edit_comment',
                    'switch_theme',
                    'customize_save_after',
                ];

                $this->assertContains($hook, $expected, "Unexpected hook: $hook");
            });

        $this->invalidator->hooks();
    }

    public function testOnPostStatusChangeSkipsSameStatus(): void
    {
        $post = new \stdClass();
        $post->ID = 1;

        $this->pageCache->expects($this->never())->method('invalidate');
        $this->pageCache->expects($this->never())->method('flush');

        $this->invalidator->onPostStatusChange('publish', 'publish', $post);
    }

    public function testOnPostSavedInvalidatesPermalink(): void
    {
        $this->contentQuery->method('getPermalink')
            ->with(42)
            ->willReturn('https://example.com/my-post');

        $this->pageCache->expects($this->once())
            ->method('invalidate')
            ->with('https://example.com/my-post');

        $this->invalidator->onPostSaved(42);
    }

    public function testOnCommentChangeInvalidatesPost(): void
    {
        $comment = new \stdClass();
        $comment->comment_post_ID = '42';

        $this->contentQuery->method('getComment')
            ->with(99)
            ->willReturn($comment);

        $this->contentQuery->method('getPermalink')
            ->with(42)
            ->willReturn('https://example.com/my-post');

        $this->pageCache->expects($this->once())
            ->method('invalidate')
            ->with('https://example.com/my-post');

        $this->invalidator->onCommentChange(99);
    }

    public function testOnCommentChangeSkipsNullComment(): void
    {
        $this->contentQuery->method('getComment')->willReturn(null);

        $this->pageCache->expects($this->never())->method('invalidate');

        $this->invalidator->onCommentChange(99);
    }

    public function testFlushAllDelegatesToPageCache(): void
    {
        $this->pageCache->expects($this->once())->method('flush');

        $this->invalidator->flushAll();
    }

    public function testOnPostStatusChangeFlushesOnPublish(): void
    {
        $post = new \stdClass();
        $post->ID = 1;

        $this->contentQuery->method('getPermalink')->willReturn('https://example.com/post');

        $this->pageCache->expects($this->once())->method('invalidate');
        $this->pageCache->expects($this->once())->method('flush');

        $this->invalidator->onPostStatusChange('publish', 'draft', $post);
    }
}
