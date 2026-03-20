<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Tests;

use BackTo\Framework\Contracts\ContentQueryInterface;
use BackTo\Framework\Contracts\SiteContextInterface;
use BackTo\Framework\Bundle\Seo\Schema\Generator\ArticleSchemaGenerator;
use PHPUnit\Framework\TestCase;

class ArticleSchemaGeneratorTest extends TestCase
{
    private ContentQueryInterface $contentQuery;
    private SiteContextInterface $siteContext;
    private ArticleSchemaGenerator $generator;

    protected function setUp(): void
    {
        $this->contentQuery = $this->createMock(ContentQueryInterface::class);
        $this->siteContext = $this->createMock(SiteContextInterface::class);
        $this->siteContext->method('getHomeUrl')->willReturn('https://example.com');
        $this->generator = new ArticleSchemaGenerator($this->contentQuery, $this->siteContext);
    }

    public function testReturnsNullWhenPostNotFound(): void
    {
        $this->contentQuery->method('getPost')->willReturn(null);

        $this->assertNull($this->generator->generate(999));
    }

    public function testReturnsNullForNonPostType(): void
    {
        $post = new \stdClass();
        $post->post_type = 'page';

        $this->contentQuery->method('getPost')->willReturn($post);

        $this->assertNull($this->generator->generate(1));
    }

    public function testGeneratesArticleSchema(): void
    {
        $post = new \stdClass();
        $post->ID = 42;
        $post->post_type = 'post';
        $post->post_title = 'Test Article';
        $post->post_author = 1;

        $this->contentQuery->method('getPost')->willReturn($post);
        $this->contentQuery->method('getPermalink')->willReturn('https://example.com/test-article');
        $this->contentQuery->method('getTheDate')->willReturn('2024-01-15T10:00:00+00:00');
        $this->contentQuery->method('getTheModifiedDate')->willReturn('2024-01-16T10:00:00+00:00');
        $this->contentQuery->method('getUserdata')->willReturn(false);
        $this->contentQuery->method('getThePostThumbnailUrl')->willReturn(false);
        $this->contentQuery->method('getTheExcerpt')->willReturn('');

        $schema = $this->generator->generate(42);

        $this->assertNotNull($schema);
        $this->assertSame('Article', $schema->getType());

        $data = $schema->toArray();
        $this->assertSame('Test Article', $data['headline']);
        $this->assertSame('https://example.com/test-article', $data['url']);
    }

    public function testIncludesAuthorWhenAvailable(): void
    {
        $post = new \stdClass();
        $post->ID = 42;
        $post->post_type = 'post';
        $post->post_title = 'Test';
        $post->post_author = 1;

        $author = new \stdClass();
        $author->display_name = 'John Doe';

        $this->contentQuery->method('getPost')->willReturn($post);
        $this->contentQuery->method('getPermalink')->willReturn('https://example.com/test');
        $this->contentQuery->method('getTheDate')->willReturn('2024-01-15');
        $this->contentQuery->method('getTheModifiedDate')->willReturn('2024-01-15');
        $this->contentQuery->method('getUserdata')->with(1)->willReturn($author);
        $this->contentQuery->method('getThePostThumbnailUrl')->willReturn(false);
        $this->contentQuery->method('getTheExcerpt')->willReturn('');

        $schema = $this->generator->generate(42);
        $data = $schema->toArray();

        $this->assertArrayHasKey('author', $data);
    }
}
