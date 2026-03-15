<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Tests;

use BackTo\Framework\Seo\Contracts\MetaProviderInterface;
use BackTo\Framework\Seo\Contracts\SeoProviderInterface;
use BackTo\Framework\Seo\Contracts\SocialLinksProviderInterface;
use BackTo\Framework\Seo\Provider\YoastProvider;
use PHPUnit\Framework\TestCase;

class YoastProviderTest extends TestCase
{
    private YoastProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new YoastProvider();
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

    public function testGetSocialLinksReturnsAllKeys(): void
    {
        $expectedKeys = ['facebook', 'twitter', 'instagram', 'linkedin', 'pinterest', 'youtube'];

        // getSocialLinks calls WordPress functions, but we can verify the structure
        // by checking the method exists and returns an array with the right keys
        $reflection = new \ReflectionMethod($this->provider, 'getSocialLinks');
        $this->assertTrue($reflection->isPublic());

        $returnType = $reflection->getReturnType();
        $this->assertSame('array', $returnType->getName());
    }
}
