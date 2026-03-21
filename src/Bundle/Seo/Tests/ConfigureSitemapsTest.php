<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Seo\Hooks\ConfigureSitemaps;
use PHPUnit\Framework\TestCase;

class ConfigureSitemapsTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private ConfigureSitemaps $sitemaps;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->sitemaps = new ConfigureSitemaps($this->dispatcher);
    }

    public function testImplementsHooksInterface(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->sitemaps);
    }

    public function testDefaults(): void
    {
        $this->assertTrue($this->sitemaps->isEnabled());
        $this->assertTrue($this->sitemaps->isUsersEnabled());
        $this->assertSame([], $this->sitemaps->getExcludedPostTypes());
        $this->assertSame([], $this->sitemaps->getExcludedTaxonomies());
        $this->assertSame([], $this->sitemaps->getExcludedPostIds());
        $this->assertSame([], $this->sitemaps->getExcludedTermIds());
        $this->assertSame(2000, $this->sitemaps->getMaxUrls());
    }

    public function testSetEnabled(): void
    {
        $result = $this->sitemaps->setEnabled(false);

        $this->assertSame($this->sitemaps, $result);
        $this->assertFalse($this->sitemaps->isEnabled());
    }

    public function testSetUsersEnabled(): void
    {
        $result = $this->sitemaps->setUsersEnabled(false);

        $this->assertSame($this->sitemaps, $result);
        $this->assertFalse($this->sitemaps->isUsersEnabled());
    }

    public function testSetExcludedPostTypes(): void
    {
        $result = $this->sitemaps->setExcludedPostTypes(['attachment', 'revision']);

        $this->assertSame($this->sitemaps, $result);
        $this->assertSame(['attachment', 'revision'], $this->sitemaps->getExcludedPostTypes());
    }

    public function testSetExcludedTaxonomies(): void
    {
        $this->sitemaps->setExcludedTaxonomies(['post_tag']);
        $this->assertSame(['post_tag'], $this->sitemaps->getExcludedTaxonomies());
    }

    public function testSetExcludedPostIds(): void
    {
        $this->sitemaps->setExcludedPostIds([10, 20, 30]);
        $this->assertSame([10, 20, 30], $this->sitemaps->getExcludedPostIds());
    }

    public function testSetExcludedTermIds(): void
    {
        $this->sitemaps->setExcludedTermIds([5, 15]);
        $this->assertSame([5, 15], $this->sitemaps->getExcludedTermIds());
    }

    public function testSetMaxUrls(): void
    {
        $this->sitemaps->setMaxUrls(1000);
        $this->assertSame(1000, $this->sitemaps->getMaxUrls());
    }

    public function testHooksDisablesSitemapsWhenNotEnabled(): void
    {
        $this->sitemaps->setEnabled(false);

        $this->dispatcher->expects($this->once())
            ->method('addFilter')
            ->with('wp_sitemaps_enabled', '__return_false');

        $this->sitemaps->hooks();
    }

    public function testHooksRegistersNoFiltersWithDefaults(): void
    {
        // With all defaults, no filters should be registered
        $this->dispatcher->expects($this->never())->method('addFilter');

        $this->sitemaps->hooks();
    }

    public function testHooksRegistersPostTypeFilter(): void
    {
        $this->sitemaps->setExcludedPostTypes(['attachment']);

        $this->dispatcher->expects($this->once())
            ->method('addFilter')
            ->with('wp_sitemaps_post_types', [$this->sitemaps, 'filterPostTypes']);

        $this->sitemaps->hooks();
    }

    public function testHooksRegistersTaxonomyFilter(): void
    {
        $this->sitemaps->setExcludedTaxonomies(['post_tag']);

        $this->dispatcher->expects($this->once())
            ->method('addFilter')
            ->with('wp_sitemaps_taxonomies', [$this->sitemaps, 'filterTaxonomies']);

        $this->sitemaps->hooks();
    }

    public function testHooksRegistersUsersProviderRemoval(): void
    {
        $this->sitemaps->setUsersEnabled(false);

        $this->dispatcher->expects($this->once())
            ->method('addFilter')
            ->with('wp_sitemaps_add_provider', [$this->sitemaps, 'removeUsersProvider'], 10, 2);

        $this->sitemaps->hooks();
    }

    public function testHooksRegistersMaxUrlsFilter(): void
    {
        $this->sitemaps->setMaxUrls(500);

        $this->dispatcher->expects($this->once())
            ->method('addFilter')
            ->with('wp_sitemaps_max_urls', [$this->sitemaps, 'filterMaxUrls']);

        $this->sitemaps->hooks();
    }

    public function testFilterPostTypesRemovesExcluded(): void
    {
        $this->sitemaps->setExcludedPostTypes(['attachment']);

        $postTypes = [
            'post' => (object) ['name' => 'post'],
            'page' => (object) ['name' => 'page'],
            'attachment' => (object) ['name' => 'attachment'],
        ];

        $result = $this->sitemaps->filterPostTypes($postTypes);

        $this->assertArrayHasKey('post', $result);
        $this->assertArrayHasKey('page', $result);
        $this->assertArrayNotHasKey('attachment', $result);
    }

    public function testFilterTaxonomiesRemovesExcluded(): void
    {
        $this->sitemaps->setExcludedTaxonomies(['post_tag', 'post_format']);

        $taxonomies = [
            'category' => (object) ['name' => 'category'],
            'post_tag' => (object) ['name' => 'post_tag'],
            'post_format' => (object) ['name' => 'post_format'],
        ];

        $result = $this->sitemaps->filterTaxonomies($taxonomies);

        $this->assertArrayHasKey('category', $result);
        $this->assertArrayNotHasKey('post_tag', $result);
        $this->assertArrayNotHasKey('post_format', $result);
    }

    public function testRemoveUsersProviderReturnsNullForUsers(): void
    {
        $provider = new \stdClass();

        $this->assertNull($this->sitemaps->removeUsersProvider($provider, 'users'));
    }

    public function testRemoveUsersProviderKeepsOtherProviders(): void
    {
        $provider = new \stdClass();

        $this->assertSame($provider, $this->sitemaps->removeUsersProvider($provider, 'posts'));
    }

    public function testFilterMaxUrls(): void
    {
        $this->sitemaps->setMaxUrls(500);

        $this->assertSame(500, $this->sitemaps->filterMaxUrls(2000));
    }

    public function testExcludePostIds(): void
    {
        $this->sitemaps->setExcludedPostIds([10, 20]);

        $args = ['post_type' => 'post'];
        $result = $this->sitemaps->excludePostIds($args);

        $this->assertSame([10, 20], $result['post__not_in']);
    }

    public function testExcludePostIdsMergesWithExisting(): void
    {
        $this->sitemaps->setExcludedPostIds([30]);

        $args = ['post__not_in' => [10, 20]];
        $result = $this->sitemaps->excludePostIds($args);

        $this->assertSame([10, 20, 30], $result['post__not_in']);
    }

    public function testExcludeTermIds(): void
    {
        $this->sitemaps->setExcludedTermIds([5, 15]);

        $args = ['taxonomy' => 'category'];
        $result = $this->sitemaps->excludeTermIds($args);

        $this->assertSame([5, 15], $result['exclude']);
    }

    public function testExcludeTermIdsMergesWithExisting(): void
    {
        $this->sitemaps->setExcludedTermIds([25]);

        $args = ['exclude' => [5, 15]];
        $result = $this->sitemaps->excludeTermIds($args);

        $this->assertSame([5, 15, 25], $result['exclude']);
    }
}
