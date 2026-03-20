<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Tests;

use BackTo\Framework\Contracts\ContentQueryInterface;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Performance\Hooks\PreloadPageCache;
use BackTo\Framework\Performance\PreloadExecutor;
use BackTo\Framework\Performance\PreloadUrlCollector;
use BackTo\Framework\Queue\Contracts\CronSchedulerInterface;
use PHPUnit\Framework\TestCase;

class PreloadPageCacheTest extends TestCase
{
    private PreloadPageCache $preloader;
    private HookDispatcherInterface $hookDispatcher;
    private ContentQueryInterface $contentQuery;
    private PreloadUrlCollector $urlCollector;
    private PreloadExecutor $executor;
    private CronSchedulerInterface $cronScheduler;

    protected function setUp(): void
    {
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->contentQuery = $this->createMock(ContentQueryInterface::class);
        $this->urlCollector = $this->createMock(PreloadUrlCollector::class);
        $this->executor = $this->createMock(PreloadExecutor::class);
        $this->cronScheduler = $this->createMock(CronSchedulerInterface::class);
        $this->preloader = new PreloadPageCache(
            $this->hookDispatcher,
            $this->contentQuery,
            $this->urlCollector,
            $this->executor,
            $this->cronScheduler,
        );
    }

    public function testHooksRegistersAllActions(): void
    {
        $this->hookDispatcher->expects($this->exactly(6))
            ->method('addAction')
            ->willReturnCallback(function (string $hook) {
                static $calls = [];
                $calls[] = $hook;

                $expected = [
                    'save_post',
                    'transition_post_status',
                    'switch_theme',
                    'customize_save_after',
                    PreloadPageCache::CRON_HOOK,
                    PreloadPageCache::CRON_FULL_HOOK,
                ];

                $this->assertContains($hook, $expected, "Unexpected hook: $hook");
            });

        $this->preloader->hooks();
    }

    public function testCronHookConstants(): void
    {
        $this->assertSame('btf_preload_page_cache', PreloadPageCache::CRON_HOOK);
        $this->assertSame('btf_preload_page_cache_full', PreloadPageCache::CRON_FULL_HOOK);
    }

    public function testSchedulePostPreloadSchedulesCronForPublishedPost(): void
    {
        $post = new \stdClass();
        $post->post_status = 'publish';

        $this->contentQuery->method('getPost')->with(42)->willReturn($post);
        $this->contentQuery->method('isPostRevision')->with(42)->willReturn(false);
        $this->contentQuery->method('isPostAutosave')->with(42)->willReturn(false);

        $this->cronScheduler->expects($this->once())->method('clear');
        $this->cronScheduler->expects($this->once())->method('scheduleSingle');
        $this->cronScheduler->expects($this->once())->method('spawn');

        $this->preloader->schedulePostPreload(42);
    }

    public function testSchedulePostPreloadSkipsNullPost(): void
    {
        $this->contentQuery->method('getPost')->willReturn(null);

        $this->cronScheduler->expects($this->never())->method('scheduleSingle');

        $this->preloader->schedulePostPreload(42);
    }

    public function testSchedulePostPreloadSkipsDraftPost(): void
    {
        $post = new \stdClass();
        $post->post_status = 'draft';

        $this->contentQuery->method('getPost')->willReturn($post);

        $this->cronScheduler->expects($this->never())->method('scheduleSingle');

        $this->preloader->schedulePostPreload(42);
    }

    public function testSchedulePostPreloadSkipsRevision(): void
    {
        $post = new \stdClass();
        $post->post_status = 'publish';

        $this->contentQuery->method('getPost')->willReturn($post);
        $this->contentQuery->method('isPostRevision')->with(42)->willReturn(true);

        $this->cronScheduler->expects($this->never())->method('scheduleSingle');

        $this->preloader->schedulePostPreload(42);
    }

    public function testSchedulePostPreloadSkipsAutosave(): void
    {
        $post = new \stdClass();
        $post->post_status = 'publish';

        $this->contentQuery->method('getPost')->willReturn($post);
        $this->contentQuery->method('isPostRevision')->willReturn(false);
        $this->contentQuery->method('isPostAutosave')->with(42)->willReturn(true);

        $this->cronScheduler->expects($this->never())->method('scheduleSingle');

        $this->preloader->schedulePostPreload(42);
    }

    public function testScheduleOnPublishSkipsSameStatus(): void
    {
        $post = new \stdClass();
        $post->ID = 42;

        $this->cronScheduler->expects($this->never())->method('scheduleSingle');

        $this->preloader->scheduleOnPublish('publish', 'publish', $post);
    }

    public function testScheduleOnPublishSkipsNonPublishTransitions(): void
    {
        $post = new \stdClass();
        $post->ID = 42;

        $this->cronScheduler->expects($this->never())->method('scheduleSingle');

        $this->preloader->scheduleOnPublish('pending', 'draft', $post);
    }

    public function testExecutePostPreloadDelegatesToCollectorAndExecutor(): void
    {
        $urls = ['https://example.com/post-1', 'https://example.com/'];

        $this->urlCollector->expects($this->once())
            ->method('getPostRelatedUrls')
            ->with(42)
            ->willReturn($urls);

        $this->executor->expects($this->once())
            ->method('preload')
            ->with($urls);

        $this->preloader->executePostPreload(42);
    }

    public function testExecuteFullPreloadDelegatesToCollectorAndExecutor(): void
    {
        $urls = ['https://example.com/', 'https://example.com/blog'];

        $this->urlCollector->expects($this->once())
            ->method('getSiteUrls')
            ->willReturn($urls);

        $this->executor->expects($this->once())
            ->method('preload')
            ->with($urls);

        $this->preloader->executeFullPreload();
    }

    public function testScheduleFullPreloadClearsAndSchedules(): void
    {
        $this->cronScheduler->expects($this->once())
            ->method('clear')
            ->with(PreloadPageCache::CRON_FULL_HOOK);

        $this->cronScheduler->expects($this->once())
            ->method('scheduleSingle')
            ->with(
                PreloadPageCache::CRON_FULL_HOOK,
                $this->greaterThan(time()),
            );

        $this->cronScheduler->expects($this->once())
            ->method('spawn');

        $this->preloader->scheduleFullPreload();
    }

    public function testConstructorDefaultValues(): void
    {
        $preloader = new PreloadPageCache(
            $this->hookDispatcher,
            $this->contentQuery,
            $this->urlCollector,
            $this->executor,
            $this->cronScheduler,
        );
        $this->assertInstanceOf(PreloadPageCache::class, $preloader);
    }

    public function testConstructorCustomDelay(): void
    {
        $preloader = new PreloadPageCache(
            $this->hookDispatcher,
            $this->contentQuery,
            $this->urlCollector,
            $this->executor,
            $this->cronScheduler,
            10,
        );
        $this->assertInstanceOf(PreloadPageCache::class, $preloader);
    }
}
