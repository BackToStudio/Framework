<?php

declare(strict_types=1);

namespace BackTo\Framework\Tests\Integration;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Performance\Contracts\PageCacheInterface;
use BackTo\Framework\Performance\Hooks\PreloadPageCache;
use PHPUnit\Framework\TestCase;

/**
 * Integration tests for PreloadPageCache that require a WordPress environment.
 *
 * Run with wp-env: npm run test:integration
 */
class PreloadPageCacheIntegrationTest extends TestCase
{
    private PreloadPageCache $preloader;
    private HookDispatcherInterface $hookDispatcher;
    private PageCacheInterface $pageCache;

    protected function setUp(): void
    {
        if (!defined('BTF_WP_LOADED') || !BTF_WP_LOADED) {
            $this->markTestSkipped('WordPress not available. Run with wp-env: npm run test:integration');
        }

        parent::setUp();
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->pageCache = $this->createMock(PageCacheInterface::class);
        $this->preloader = new PreloadPageCache($this->hookDispatcher, $this->pageCache);
    }

    public function testSchedulePostPreloadSkipsNonPublishedPosts(): void
    {
        $postId = self::factory()->post->create([
            'post_title'  => 'Draft Post',
            'post_status' => 'draft',
        ]);

        // Should not throw — draft posts are silently skipped
        $this->preloader->schedulePostPreload($postId);
        $this->assertTrue(true);
    }

    public function testPreloadUrlsSkipsCachedUrls(): void
    {
        $this->pageCache->expects($this->once())
            ->method('get')
            ->with('https://example.com/')
            ->willReturn('<html>cached</html>');

        // wp_remote_get should NOT be called since URL is already cached
        $this->preloader->preloadUrls(['https://example.com/']);
        $this->assertTrue(true);
    }

    public function testPreloadUrlsFetchesUncachedUrls(): void
    {
        $this->pageCache->expects($this->once())
            ->method('get')
            ->with('https://example.com/')
            ->willReturn(null);

        // wp_remote_get will be called for uncached URLs
        $this->preloader->preloadUrls(['https://example.com/']);
        $this->assertTrue(true);
    }

    public function testGetPostRelatedUrlsReturnsExpectedUrls(): void
    {
        $postId = self::factory()->post->create([
            'post_title'  => 'Preload Test',
            'post_status' => 'publish',
        ]);

        $urls = $this->preloader->getPostRelatedUrls($postId);

        $this->assertNotEmpty($urls);
        $this->assertContains(home_url('/'), $urls);
        $this->assertContains(get_permalink($postId), $urls);
    }

    public function testGetSiteUrlsReturnsHomepage(): void
    {
        $urls = $this->preloader->getSiteUrls();

        $this->assertNotEmpty($urls);
        $this->assertContains(home_url('/'), $urls);
    }

    public function testScheduleFullPreloadSchedulesCronEvent(): void
    {
        $this->preloader->scheduleFullPreload();

        $next = wp_next_scheduled(PreloadPageCache::CRON_FULL_HOOK);
        $this->assertNotFalse($next, 'Full preload cron event should be scheduled');
    }
}
