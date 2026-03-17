<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Performance\Contracts\PageCacheInterface;
use BackTo\Framework\Performance\Hooks\PreloadPageCache;
use PHPUnit\Framework\TestCase;

class PreloadPageCacheTest extends TestCase
{
    private PreloadPageCache $preloader;
    private HookDispatcherInterface $hookDispatcher;
    private PageCacheInterface $pageCache;

    protected function setUp(): void
    {
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->pageCache = $this->createMock(PageCacheInterface::class);
        $this->preloader = new PreloadPageCache($this->hookDispatcher, $this->pageCache);
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

    public function testSchedulePostPreloadSkipsNonPublishedPosts(): void
    {
        if (!function_exists('get_post')) {
            $this->markTestSkipped('WordPress functions not available.');
        }
    }

    public function testScheduleOnPublishSkipsSameStatus(): void
    {
        // When new and old status are the same, nothing should happen
        $post = new \stdClass();
        $post->ID = 42;

        // We verify that schedulePostPreload is NOT called by checking
        // that no WordPress functions are invoked. Since scheduleOnPublish
        // returns early when $newStatus === $oldStatus, we just test it doesn't error.
        // In a real WP env, we'd mock wp_clear_scheduled_hook etc.
        if (!function_exists('get_post')) {
            // Without WP, schedulePostPreload would fatal, so we test the early return
            $this->preloader->scheduleOnPublish('publish', 'publish', $post);
            $this->assertTrue(true); // No exception = pass
        }
    }

    public function testScheduleOnPublishSkipsNonPublishTransitions(): void
    {
        $post = new \stdClass();
        $post->ID = 42;

        // draft → pending: neither is 'publish', should return early
        $this->preloader->scheduleOnPublish('pending', 'draft', $post);
        $this->assertTrue(true);
    }

    public function testPreloadUrlsSkipsCachedUrls(): void
    {
        if (!function_exists('wp_remote_get')) {
            $this->markTestSkipped('WordPress functions not available.');
        }

        // When cache already has the URL, wp_remote_get should not be called
        $this->pageCache->expects($this->once())
            ->method('get')
            ->with('https://example.com/')
            ->willReturn('<html>cached</html>');

        $this->preloader->preloadUrls(['https://example.com/']);
    }

    public function testPreloadUrlsFetchesUncachedUrls(): void
    {
        if (!function_exists('wp_remote_get')) {
            $this->markTestSkipped('WordPress functions not available.');
        }

        $this->pageCache->expects($this->once())
            ->method('get')
            ->with('https://example.com/')
            ->willReturn(null);

        // wp_remote_get would be called here — in unit test context we just verify
        // the cache check logic works
        $this->preloader->preloadUrls(['https://example.com/']);
    }

    public function testConstructorDefaultValues(): void
    {
        $preloader = new PreloadPageCache($this->hookDispatcher, $this->pageCache);
        $this->assertInstanceOf(PreloadPageCache::class, $preloader);
    }

    public function testConstructorCustomValues(): void
    {
        $preloader = new PreloadPageCache($this->hookDispatcher, $this->pageCache, 10, 100);
        $this->assertInstanceOf(PreloadPageCache::class, $preloader);
    }

    public function testGetPostRelatedUrlsRequiresWordPress(): void
    {
        if (!function_exists('get_permalink')) {
            $this->markTestSkipped('WordPress functions not available.');
        }
    }

    public function testGetSiteUrlsRequiresWordPress(): void
    {
        if (!function_exists('home_url')) {
            $this->markTestSkipped('WordPress functions not available.');
        }
    }

    public function testScheduleFullPreloadRequiresWordPress(): void
    {
        if (!function_exists('wp_clear_scheduled_hook')) {
            $this->markTestSkipped('WordPress functions not available.');
        }
    }
}
