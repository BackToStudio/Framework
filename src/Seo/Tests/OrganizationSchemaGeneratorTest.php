<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Tests;

use BackTo\Framework\Contracts\SiteContextInterface;
use BackTo\Framework\Seo\Schema\Generator\OrganizationSchemaGenerator;
use BackTo\Framework\Seo\SeoManager;
use PHPUnit\Framework\TestCase;

class OrganizationSchemaGeneratorTest extends TestCase
{
    private SiteContextInterface $siteContext;

    protected function setUp(): void
    {
        $this->siteContext = $this->createMock(SiteContextInterface::class);
        $this->siteContext->method('getHomeUrl')->willReturn('https://example.com');
        $this->siteContext->method('getBlogInfo')->willReturn('My Site');
        $this->siteContext->method('getThemeMod')->willReturn(false);
    }

    public function testGenerateProducesOrganizationSchema(): void
    {
        $seoManager = new SeoManager([]);

        $generator = new OrganizationSchemaGenerator($seoManager, $this->siteContext);
        $schema = $generator->generate();

        $this->assertSame('Organization', $schema->getType());
        $data = $schema->toArray();
        $this->assertSame('https://example.com/#organization', $data['@id']);
        $this->assertSame('My Site', $data['name']);
    }

    public function testGenerateIncludesLogoWhenAvailable(): void
    {
        $siteContext = $this->createMock(SiteContextInterface::class);
        $siteContext->method('getHomeUrl')->willReturn('https://example.com');
        $siteContext->method('getBlogInfo')->willReturn('My Site');
        $siteContext->method('getThemeMod')->with('custom_logo')->willReturn(42);
        $siteContext->method('getAttachmentImageUrl')->with(42, 'full')->willReturn('https://example.com/logo.png');

        $seoManager = new SeoManager([]);

        $generator = new OrganizationSchemaGenerator($seoManager, $siteContext);
        $schema = $generator->generate();

        $data = $schema->toArray();
        $this->assertSame('https://example.com/logo.png', $data['logo']);
    }
}
