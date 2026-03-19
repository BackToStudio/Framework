<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Tests;

use BackTo\Framework\Contracts\SiteContextInterface;
use BackTo\Framework\Seo\Schema\Generator\WebSiteSchemaGenerator;
use PHPUnit\Framework\TestCase;

class WebSiteSchemaGeneratorTest extends TestCase
{
    public function testGenerateProducesWebSiteSchema(): void
    {
        $siteContext = $this->createMock(SiteContextInterface::class);
        $siteContext->method('getHomeUrl')->willReturn('https://example.com');
        $siteContext->method('getBlogInfo')->willReturnMap([
            ['name', 'My Site'],
            ['description', 'A test site'],
        ]);

        $generator = new WebSiteSchemaGenerator($siteContext);
        $schema = $generator->generate();

        $this->assertSame('WebSite', $schema->getType());
        $data = $schema->toArray();
        $this->assertSame('https://example.com/#website', $data['@id']);
        $this->assertSame('My Site', $data['name']);
        $this->assertSame('https://example.com/', $data['url']);
        $this->assertSame('A test site', $data['description']);
    }
}
