<?php

namespace BackTo\Framework\Seo\Tests;

use BackTo\Framework\Seo\Contracts\SeoProviderInterface;
use BackTo\Framework\Seo\SeoManager;
use PHPUnit\Framework\TestCase;

class SeoManagerTest extends TestCase
{
    public function testNoProvidersByDefault(): void
    {
        $manager = new SeoManager();
        $this->assertFalse($manager->hasProvider());
        $this->assertNull($manager->getProvider());
    }

    public function testGetSocialLinksReturnsEmptyWhenNoProvider(): void
    {
        $manager = new SeoManager();
        $this->assertSame([], $manager->getSocialLinks());
    }

    public function testResolvesFirstActiveProvider(): void
    {
        $inactive = $this->createProvider('inactive', false);
        $active = $this->createProvider('active', true);

        $manager = new SeoManager([$inactive, $active]);

        $this->assertTrue($manager->hasProvider());
        $this->assertSame($active, $manager->getProvider());
    }

    public function testSkipsInactiveProviders(): void
    {
        $inactive1 = $this->createProvider('a', false);
        $inactive2 = $this->createProvider('b', false);

        $manager = new SeoManager([$inactive1, $inactive2]);

        $this->assertFalse($manager->hasProvider());
        $this->assertNull($manager->getProvider());
    }

    public function testFirstActiveWins(): void
    {
        $first = $this->createProvider('first', true);
        $second = $this->createProvider('second', true);

        $manager = new SeoManager([$first, $second]);

        $this->assertSame('first', $manager->getProvider()->getName());
    }

    public function testAddProvider(): void
    {
        $manager = new SeoManager();
        $this->assertFalse($manager->hasProvider());

        $provider = $this->createProvider('added', true);
        $result = $manager->addProvider($provider);

        $this->assertSame($manager, $result);
        $this->assertTrue($manager->hasProvider());
        $this->assertSame($provider, $manager->getProvider());
    }

    public function testAddProviderResetsResolution(): void
    {
        $manager = new SeoManager();
        $this->assertFalse($manager->hasProvider());

        // Force resolution (caches null)
        $manager->getProvider();

        // Add a provider after resolution
        $provider = $this->createProvider('late', true);
        $manager->addProvider($provider);

        // Should re-resolve and find the new provider
        $this->assertTrue($manager->hasProvider());
    }

    public function testGetSocialLinksDelegatesToProvider(): void
    {
        $expectedLinks = [
            'facebook' => 'https://facebook.com/test',
            'twitter' => 'https://twitter.com/test',
            'instagram' => null,
            'linkedin' => null,
            'pinterest' => null,
            'youtube' => null,
        ];

        $provider = $this->createMock(SeoProviderInterface::class);
        $provider->method('isActive')->willReturn(true);
        $provider->method('getSocialLinks')->willReturn($expectedLinks);

        $manager = new SeoManager([$provider]);

        $this->assertSame($expectedLinks, $manager->getSocialLinks());
    }

    public function testCachesProviderResolution(): void
    {
        $provider = $this->createMock(SeoProviderInterface::class);
        $provider->expects($this->exactly(1))->method('isActive')->willReturn(true);

        $manager = new SeoManager([$provider]);

        // Call multiple times — isActive should only be called once
        $manager->getProvider();
        $manager->getProvider();
        $manager->hasProvider();
    }

    private function createProvider(string $name, bool $active): SeoProviderInterface
    {
        $provider = $this->createMock(SeoProviderInterface::class);
        $provider->method('getName')->willReturn($name);
        $provider->method('isActive')->willReturn($active);

        return $provider;
    }
}
