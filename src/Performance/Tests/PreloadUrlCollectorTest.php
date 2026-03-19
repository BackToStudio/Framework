<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Tests;

use BackTo\Framework\Contracts\ContentQueryInterface;
use BackTo\Framework\Contracts\SiteContextInterface;
use BackTo\Framework\Performance\PreloadUrlCollector;
use PHPUnit\Framework\TestCase;

class PreloadUrlCollectorTest extends TestCase
{
    private ContentQueryInterface $contentQuery;
    private SiteContextInterface $siteContext;

    protected function setUp(): void
    {
        $this->contentQuery = $this->createMock(ContentQueryInterface::class);
        $this->siteContext = $this->createMock(SiteContextInterface::class);
        $this->siteContext->method('getHomeUrl')->willReturn('https://example.com');
    }

    private function createCollector(int $batchSize = 50): PreloadUrlCollector
    {
        return new PreloadUrlCollector($this->contentQuery, $this->siteContext, $batchSize);
    }

    public function testGetSiteUrlsIncludesHomeUrl(): void
    {
        $this->contentQuery->method('getOption')->willReturn(0);
        $this->contentQuery->method('getPosts')->willReturn([]);
        $this->contentQuery->method('getCategories')->willReturn([]);
        $this->contentQuery->method('getTags')->willReturn([]);

        $urls = $this->createCollector()->getSiteUrls();

        $this->assertContains('https://example.com/', $urls);
    }

    public function testGetSiteUrlsIncludesRecentPosts(): void
    {
        $post = new \stdClass();
        $post->ID = 1;

        $this->contentQuery->method('getOption')->willReturn(0);
        $this->contentQuery->method('getPosts')->willReturnCallback(function (array $args) use ($post) {
            if ($args['post_type'] === 'post') {
                return [$post];
            }
            return [];
        });
        $this->contentQuery->method('getPermalink')->with(1)->willReturn('https://example.com/post-1');
        $this->contentQuery->method('getCategories')->willReturn([]);
        $this->contentQuery->method('getTags')->willReturn([]);

        $urls = $this->createCollector()->getSiteUrls();

        $this->assertContains('https://example.com/post-1', $urls);
    }

    public function testGetSiteUrlsRespectsBatchSize(): void
    {
        $posts = [];
        for ($i = 1; $i <= 10; $i++) {
            $post = new \stdClass();
            $post->ID = $i;
            $posts[] = $post;
        }

        $this->contentQuery->method('getOption')->willReturn(0);
        $this->contentQuery->method('getPosts')->willReturn($posts);
        $this->contentQuery->method('getPermalink')->willReturnCallback(fn ($id) => "https://example.com/post-{$id}");
        $this->contentQuery->method('getCategories')->willReturn([]);
        $this->contentQuery->method('getTags')->willReturn([]);

        $urls = $this->createCollector(5)->getSiteUrls();

        $this->assertCount(5, $urls);
    }

    public function testGetPostRelatedUrlsCollectsPermalink(): void
    {
        $post = new \stdClass();
        $post->ID = 42;
        $post->post_author = 1;
        $post->post_date = '2024-01-15 10:00:00';

        $this->contentQuery->method('getPostType')->with(42)->willReturn('post');
        $this->contentQuery->method('getPost')->with(42)->willReturn($post);
        $this->contentQuery->method('getPermalink')->willReturn('https://example.com/my-post');
        $this->contentQuery->method('getOption')->willReturn(0);
        $this->contentQuery->method('getPostTypeArchiveLink')->willReturn(false);
        $this->contentQuery->method('getObjectTaxonomies')->willReturn([]);
        $this->contentQuery->method('getPostTerms')->willReturn([]);
        $this->contentQuery->method('getAuthorPostsUrl')->willReturn('https://example.com/author/admin');
        $this->contentQuery->method('getYearLink')->willReturn('https://example.com/2024/');
        $this->contentQuery->method('getMonthLink')->willReturn('https://example.com/2024/01/');

        $urls = $this->createCollector()->getPostRelatedUrls(42);

        $this->assertContains('https://example.com/my-post', $urls);
        $this->assertContains('https://example.com/', $urls);
    }
}
