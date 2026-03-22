<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Tests;

use BackTo\Framework\Bundle\Seo\SeoConfigurator;
use PHPUnit\Framework\TestCase;

class SeoConfiguratorSitemapTest extends TestCase
{
    private SeoConfigurator $configurator;

    protected function setUp(): void
    {
        $this->configurator = new SeoConfigurator();
    }

    public function testSitemapEnabled(): void
    {
        $result = $this->configurator->sitemapEnabled(false);

        $this->assertSame($this->configurator, $result);
        $this->assertFalse($this->configurator->toParameters()['seo.sitemap_enabled']);
    }

    public function testSitemapUsersEnabled(): void
    {
        $this->configurator->sitemapUsersEnabled(true);
        $this->assertTrue($this->configurator->toParameters()['seo.sitemap_users_enabled']);
    }

    public function testSitemapExcludePostTypes(): void
    {
        $result = $this->configurator->sitemapExcludePostTypes(['attachment', 'revision']);

        $this->assertSame($this->configurator, $result);
        $this->assertSame(
            ['attachment', 'revision'],
            $this->configurator->toParameters()['seo.sitemap_excluded_post_types'],
        );
    }

    public function testSitemapExcludeTaxonomies(): void
    {
        $this->configurator->sitemapExcludeTaxonomies(['post_tag', 'post_format']);
        $this->assertSame(
            ['post_tag', 'post_format'],
            $this->configurator->toParameters()['seo.sitemap_excluded_taxonomies'],
        );
    }

    public function testSitemapExcludePostIds(): void
    {
        $this->configurator->sitemapExcludePostIds([10, 20, 30]);
        $this->assertSame(
            [10, 20, 30],
            $this->configurator->toParameters()['seo.sitemap_excluded_post_ids'],
        );
    }

    public function testSitemapExcludeTermIds(): void
    {
        $this->configurator->sitemapExcludeTermIds([5, 15]);
        $this->assertSame(
            [5, 15],
            $this->configurator->toParameters()['seo.sitemap_excluded_term_ids'],
        );
    }

    public function testSitemapMaxUrls(): void
    {
        $this->configurator->sitemapMaxUrls(500);
        $this->assertSame(500, $this->configurator->toParameters()['seo.sitemap_max_urls']);
    }

    public function testFluentChaining(): void
    {
        $params = $this->configurator
            ->sitemapEnabled(true)
            ->sitemapUsersEnabled(false)
            ->sitemapExcludePostTypes(['attachment'])
            ->sitemapExcludeTaxonomies(['post_tag'])
            ->sitemapMaxUrls(1000)
            ->titleSeparator('-')
            ->toParameters();

        $this->assertCount(6, $params);
        $this->assertTrue($params['seo.sitemap_enabled']);
        $this->assertFalse($params['seo.sitemap_users_enabled']);
        $this->assertSame('-', $params['seo.title_separator']);
    }
}
