<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Tests;

use BackTo\Framework\Contracts\ContentQueryInterface;
use BackTo\Framework\Contracts\PluginCheckerInterface;
use BackTo\Framework\Options\Contracts\OptionsRepositoryInterface;
use BackTo\Framework\Seo\Contracts\MetaProviderInterface;
use BackTo\Framework\Seo\Contracts\SeoProviderInterface;
use BackTo\Framework\Seo\Contracts\SocialLinksProviderInterface;
use BackTo\Framework\Seo\Provider\YoastProvider;
use PHPUnit\Framework\TestCase;

class YoastProviderTest extends TestCase
{
    private YoastProvider $provider;
    private PluginCheckerInterface $pluginChecker;
    private OptionsRepositoryInterface $options;
    private ContentQueryInterface $contentQuery;

    protected function setUp(): void
    {
        $this->pluginChecker = $this->createMock(PluginCheckerInterface::class);
        $this->options = $this->createMock(OptionsRepositoryInterface::class);
        $this->contentQuery = $this->createMock(ContentQueryInterface::class);
        $this->provider = new YoastProvider(
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
        $this->assertSame('yoast', $this->provider->getName());
    }

    public function testIsActiveDelegatesToPluginChecker(): void
    {
        $this->pluginChecker->method('isActive')
            ->with('wordpress-seo/wp-seo.php')
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
            'facebook_site' => 'https://facebook.com/test',
            'twitter_site' => 'https://twitter.com/test',
            'instagram_url' => 'https://instagram.com/test',
            'linkedin_url' => 'https://linkedin.com/test',
            'pinterest_url' => 'https://pinterest.com/test',
            'youtube_url' => 'https://youtube.com/test',
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
            ->with(42, '_yoast_wpseo_title', true)
            ->willReturn('My SEO Title');

        $this->assertSame('My SEO Title', $this->provider->getTitle(42));
    }

    public function testGetTitleUsesCurrentPostIdWhenNull(): void
    {
        $this->contentQuery->method('getCurrentPostId')->willReturn(99);
        $this->contentQuery->method('getPostMeta')
            ->with(99, '_yoast_wpseo_title', true)
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
            ->with(42, '_yoast_wpseo_metadesc', true)
            ->willReturn('My description');

        $this->assertSame('My description', $this->provider->getDescription(42));
    }

    public function testGetCanonicalUrlReturnsPostMeta(): void
    {
        $this->contentQuery->method('getPostMeta')
            ->with(42, '_yoast_wpseo_canonical', true)
            ->willReturn('https://example.com/canonical');

        $this->assertSame('https://example.com/canonical', $this->provider->getCanonicalUrl(42));
    }

    public function testGetOgTitleReturnsPostMeta(): void
    {
        $this->contentQuery->method('getPostMeta')
            ->with(42, '_yoast_wpseo_opengraph-title', true)
            ->willReturn('OG Title');

        $this->assertSame('OG Title', $this->provider->getOgTitle(42));
    }

    public function testGetOgDescriptionReturnsPostMeta(): void
    {
        $this->contentQuery->method('getPostMeta')
            ->with(42, '_yoast_wpseo_opengraph-description', true)
            ->willReturn('OG Description');

        $this->assertSame('OG Description', $this->provider->getOgDescription(42));
    }

    public function testGetOgImageUrlReturnsPostMeta(): void
    {
        $this->contentQuery->method('getPostMeta')
            ->with(42, '_yoast_wpseo_opengraph-image', true)
            ->willReturn('https://example.com/image.jpg');

        $this->assertSame('https://example.com/image.jpg', $this->provider->getOgImageUrl(42));
    }

    public function testGetTitleReturnsNullWhenNoCurrentPostId(): void
    {
        $this->contentQuery->method('getCurrentPostId')->willReturn(false);

        $this->assertNull($this->provider->getTitle());
    }
}
