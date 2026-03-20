<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Hooks\Cache;

use BackTo\Framework\Contracts\ContentQueryInterface;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Performance\PreloadExecutor;
use BackTo\Framework\Bundle\Performance\PreloadUrlCollector;
use BackTo\Framework\Queue\Contracts\CronSchedulerInterface;

/**
 * Preload the page cache in the background after content changes.
 *
 * When a post is saved or published, this hook schedules a WP-Cron
 * event that re-fetches the affected URLs so the cache is warm
 * before the next visitor arrives.
 */
final class PreloadPageCache implements Hooks
{
    public const CRON_HOOK = 'btf_preload_page_cache';
    public const CRON_FULL_HOOK = 'btf_preload_page_cache_full';

    /** @var int Delay in seconds before the cron event fires */
    private readonly int $delay;

    public function __construct(
        private readonly HookDispatcherInterface $hookDispatcher,
        private readonly ContentQueryInterface $contentQuery,
        private readonly PreloadUrlCollector $urlCollector,
        private readonly PreloadExecutor $executor,
        private readonly CronSchedulerInterface $cronScheduler,
        int $delay = 5,
    ) {
        $this->delay = $delay;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('save_post', [$this, 'schedulePostPreload'], 20);
        $this->hookDispatcher->addAction('transition_post_status', [$this, 'scheduleOnPublish'], 20, 3);
        $this->hookDispatcher->addAction('switch_theme', [$this, 'scheduleFullPreload']);
        $this->hookDispatcher->addAction('customize_save_after', [$this, 'scheduleFullPreload']);
        $this->hookDispatcher->addAction(self::CRON_HOOK, [$this, 'executePostPreload']);
        $this->hookDispatcher->addAction(self::CRON_FULL_HOOK, [$this, 'executeFullPreload']);
    }

    public function schedulePostPreload(int $postId): void
    {
        $post = $this->contentQuery->getPost($postId);

        if ($post === null) {
            return;
        }

        if ($post->post_status !== 'publish') {
            return;
        }

        if ($this->contentQuery->isPostRevision($postId) || $this->contentQuery->isPostAutosave($postId)) {
            return;
        }

        $this->cronScheduler->clear(self::CRON_HOOK, [$postId]);
        $this->cronScheduler->scheduleSingle(self::CRON_HOOK, time() + $this->delay, [$postId]);
        $this->cronScheduler->spawn();
    }

    /**
     * @param object $post
     */
    public function scheduleOnPublish(string $newStatus, string $oldStatus, $post): void
    {
        if ($newStatus === $oldStatus) {
            return;
        }

        if ($newStatus !== 'publish' && $oldStatus !== 'publish') {
            return;
        }

        $this->schedulePostPreload($post->ID);
    }

    public function scheduleFullPreload(): void
    {
        $this->cronScheduler->clear(self::CRON_FULL_HOOK);
        $this->cronScheduler->scheduleSingle(self::CRON_FULL_HOOK, time() + $this->delay);
        $this->cronScheduler->spawn();
    }

    public function executePostPreload(int $postId): void
    {
        $urls = $this->urlCollector->getPostRelatedUrls($postId);
        $this->executor->preload($urls);
    }

    public function executeFullPreload(): void
    {
        $urls = $this->urlCollector->getSiteUrls();
        $this->executor->preload($urls);
    }
}
