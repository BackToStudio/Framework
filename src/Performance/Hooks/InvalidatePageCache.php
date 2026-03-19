<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Hooks;

use BackTo\Framework\Contracts\ContentQueryInterface;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Performance\Contracts\PageCacheInterface;

/**
 * Automatically invalidate the page cache when content changes.
 *
 * Listens to post save/delete, comment, and option update hooks to
 * flush the page cache and keep it in sync with the database.
 */
final class InvalidatePageCache implements Hooks
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly PageCacheInterface $pageCache;
    private readonly ContentQueryInterface $contentQuery;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        PageCacheInterface $pageCache,
        ContentQueryInterface $contentQuery,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->pageCache = $pageCache;
        $this->contentQuery = $contentQuery;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('save_post', [$this, 'onPostSaved']);
        $this->hookDispatcher->addAction('deleted_post', [$this, 'onPostDeleted']);
        $this->hookDispatcher->addAction('transition_post_status', [$this, 'onPostStatusChange'], 10, 3);
        $this->hookDispatcher->addAction('comment_post', [$this, 'onCommentChange']);
        $this->hookDispatcher->addAction('edit_comment', [$this, 'onCommentChange']);
        $this->hookDispatcher->addAction('switch_theme', [$this, 'flushAll']);
        $this->hookDispatcher->addAction('customize_save_after', [$this, 'flushAll']);
    }

    public function onPostSaved(int $postId): void
    {
        $this->invalidatePost($postId);
    }

    public function onPostDeleted(int $postId): void
    {
        $this->invalidatePost($postId);
    }


    public function onPostStatusChange(string $newStatus, string $oldStatus, $post): void
    {
        if ($newStatus === $oldStatus) {
            return;
        }

        $this->invalidatePost($post->ID);

        // When publishing or unpublishing, flush all (archives, menus, etc.)
        if ($newStatus === 'publish' || $oldStatus === 'publish') {
            $this->flushAll();
        }
    }

    public function onCommentChange(int $commentId): void
    {
        $comment = $this->contentQuery->getComment($commentId);

        if ($comment !== null) {
            $this->invalidatePost((int) $comment->comment_post_ID);
        }
    }

    public function flushAll(): void
    {
        $this->pageCache->flush();
    }

    private function invalidatePost(int $postId): void
    {
        $permalink = $this->contentQuery->getPermalink($postId);

        if ($permalink !== false) {
            $this->pageCache->invalidate($permalink);
        }
    }
}
