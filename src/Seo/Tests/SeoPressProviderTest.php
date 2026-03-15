<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Tests;

use BackTo\Framework\Seo\Contracts\MetaProviderInterface;
use BackTo\Framework\Seo\Contracts\SeoProviderInterface;
use BackTo\Framework\Seo\Contracts\SocialLinksProviderInterface;
use BackTo\Framework\Seo\Provider\SeoPressProvider;
use PHPUnit\Framework\TestCase;

class SeoPressProviderTest extends TestCase
{
    private SeoPressProvider $provider;

    protected function setUp(): void
    {
        $this->provider = new SeoPressProvider();
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

    public function testGetSocialLinksMethodExists(): void
    {
        $reflection = new \ReflectionMethod($this->provider, 'getSocialLinks');
        $this->assertTrue($reflection->isPublic());

        $returnType = $reflection->getReturnType();
        $this->assertSame('array', $returnType->getName());
    }
}
