<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Tests;

use BackTo\Framework\Contracts\ContentQueryInterface;
use BackTo\Framework\Contracts\PluginCheckerInterface;
use BackTo\Framework\Options\Contracts\OptionsRepositoryInterface;
use BackTo\Framework\Bundle\Seo\Contracts\MetaProviderInterface;
use BackTo\Framework\Bundle\Seo\Contracts\SeoProviderInterface;
use BackTo\Framework\Bundle\Seo\Contracts\SocialLinksProviderInterface;
use BackTo\Framework\Bundle\Seo\Provider\SeoPressProvider;
use PHPUnit\Framework\TestCase;

class SeoPressProviderTest extends TestCase
{
    private SeoPressProvider $provider;
    private PluginCheckerInterface $pluginChecker;
    private OptionsRepositoryInterface $options;
    private ContentQueryInterface $contentQuery;

    protected function setUp(): void
    {
        $this->pluginChecker = $this->createMock(PluginCheckerInterface::class);
        $this->options = $this->createMock(OptionsRepositoryInterface::class);
        $this->contentQuery = $this->createMock(ContentQueryInterface::class);
        $this->provider = new SeoPressProvider(
            $this->pluginChecker,
            $this->options,
            $this->contentQuery,
        );
    }

    public function testImplementsSeoProviderInterface(): void
    {
        $this->assertInstanceOf(SeoProviderInterface::class, $this->provider);
    }

    public function testImplementsSocialLinksProviderInterface(): void
    {
        $this->assertInstanceOf(SocialLinksProviderInterface::class, $this->provider);
    }

    public function testImplementsMetaProviderInterface(): void
    {
        $this->assertInstanceOf(MetaProviderInterface::class, $this->provider);
    }

    public function testGetName(): void
    {
        $this->assertSame('seopress', $this->provider->getName());
    }

    public function testIsActiveDelegatesToPluginChecker(): void
    {
        $this->pluginChecker->method('isActive')
            ->with('wp-seopress/seopress.php')
            ->willReturn(true);

        $this->assertTrue($this->provider->isActive());
    }

    public function testIsActiveReturnsFalseWhenPluginInactive(): void
    {
        $this->pluginChecker->method('isActive')->willReturn(false);

        $this->assertFalse($this->provider->isActive());
    }

    public function testGetSocialLinksReturnsAllKeys(): void
    {
        $this->options->method('get')->willReturn([
            'seopress_social_accounts_facebook' => 'https://facebook.com/test',
            'seopress_social_accounts_twitter' => 'https://twitter.com/test',
            'seopress_social_accounts_instagram' => 'https://instagram.com/test',
            'seopress_social_accounts_linkedin' => 'https://linkedin.com/test',
            'seopress_social_accounts_pinterest' => 'https://pinterest.com/test',
            'seopress_social_accounts_youtube' => 'https://youtube.com/test',
        ]);

        $links = $this->provider->getSocialLinks();

        $this->assertSame('https://facebook.com/test', $links['facebook']);
        $this->assertSame('https://twitter.com/test', $links['twitter']);
        $this->assertSame('https://instagram.com/test', $links['instagram']);
        $this->assertSame('https://linkedin.com/test', $links['linkedin']);
        $this->assertSame('https://pinterest.com/test', $links['pinterest']);
        $this->assertSame('https://youtube.com/test', $links['youtube']);
    }

    public function testGetSocialLinksReturnsNullForMissingKeys(): void
    {
        $this->options->method('get')->willReturn([]);

        $links = $this->provider->getSocialLinks();

        $this->assertNull($links['facebook']);
        $this->assertNull($links['twitter']);
    }

    public function testGetTitleReturnsPostMeta(): void
    {
        $this->contentQuery->method('getPostMeta')
            ->with(42, '_seopress_titles_title', true)
            ->willReturn('My SEO Title');

        $this->assertSame('My SEO Title', $this->provider->getTitle(42));
    }

    public function testGetTitleUsesCurrentPostIdWhenNull(): void
    {
        $this->contentQuery->method('getCurrentPostId')->willReturn(99);
        $this->contentQuery->method('getPostMeta')
            ->with(99, '_seopress_titles_title', true)
            ->willReturn('Current Post Title');

        $this->assertSame('Current Post Title', $this->provider->getTitle());
    }

    public function testGetTitleReturnsNullForEmptyMeta(): void
    {
        $this->contentQuery->method('getPostMeta')->willReturn('');

        $this->assertNull($this->provider->getTitle(42));
    }

    public function testGetDescriptionReturnsPostMeta(): void
    {
        $this->contentQuery->method('getPostMeta')
            ->with(42, '_seopress_titles_desc', true)
            ->willReturn('My description');

        $this->assertSame('My description', $this->provider->getDescription(42));
    }

    public function testGetCanonicalUrlReturnsPostMeta(): void
    {
        $this->contentQuery->method('getPostMeta')
            ->with(42, '_seopress_robots_canonical', true)
            ->willReturn('https://example.com/canonical');

        $this->assertSame('https://example.com/canonical', $this->provider->getCanonicalUrl(42));
    }

    public function testGetOgTitleReturnsPostMeta(): void
    {
        $this->contentQuery->method('getPostMeta')
            ->with(42, '_seopress_social_fb_title', true)
            ->willReturn('OG Title');

        $this->assertSame('OG Title', $this->provider->getOgTitle(42));
    }

    public function testGetOgDescriptionReturnsPostMeta(): void
    {
        $this->contentQuery->method('getPostMeta')
            ->with(42, '_seopress_social_fb_desc', true)
            ->willReturn('OG Description');

        $this->assertSame('OG Description', $this->provider->getOgDescription(42));
    }

    public function testGetOgImageUrlReturnsPostMeta(): void
    {
        $this->contentQuery->method('getPostMeta')
            ->with(42, '_seopress_social_fb_img', true)
            ->willReturn('https://example.com/image.jpg');

        $this->assertSame('https://example.com/image.jpg', $this->provider->getOgImageUrl(42));
    }

    public function testGetTitleReturnsNullWhenNoCurrentPostId(): void
    {
        $this->contentQuery->method('getCurrentPostId')->willReturn(false);

        $this->assertNull($this->provider->getTitle());
    }
}
