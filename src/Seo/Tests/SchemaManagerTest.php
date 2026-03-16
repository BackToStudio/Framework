<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Tests;

use BackTo\Framework\Seo\Schema\SchemaManager;
use BackTo\Framework\Seo\Schema\SchemaType;
use PHPUnit\Framework\TestCase;

class SchemaManagerTest extends TestCase
{
    public function testEmptyByDefault(): void
    {
        $manager = new SchemaManager();

        $this->assertFalse($manager->hasSchemas());
        $this->assertSame([], $manager->getSchemas());
        $this->assertSame([], $manager->toArray());
    }

    public function testRenderReturnsEmptyStringWhenNoSchemas(): void
    {
        $manager = new SchemaManager();
        $this->assertSame('', $manager->render());
    }

    public function testAddSchema(): void
    {
        $manager = new SchemaManager();
        $schema = new SchemaType('Organization');

        $result = $manager->add($schema);

        $this->assertSame($manager, $result, 'add() should return $this');
        $this->assertTrue($manager->hasSchemas());
        $this->assertCount(1, $manager->getSchemas());
    }

    public function testToArray(): void
    {
        $manager = new SchemaManager();
        $manager->add((new SchemaType('Organization'))->set('name', 'Acme'));
        $manager->add((new SchemaType('WebSite'))->set('name', 'My Site'));

        $array = $manager->toArray();

        $this->assertCount(2, $array);
        $this->assertSame('Organization', $array[0]['@type']);
        $this->assertSame('WebSite', $array[1]['@type']);
    }

    public function testRenderSingleSchemaWithoutGraph(): void
    {
        $manager = new SchemaManager();
        $manager->add((new SchemaType('Organization'))->set('name', 'Acme'));

        $output = $manager->render();

        $this->assertStringContainsString('<script type="application/ld+json">', $output);
        $this->assertStringContainsString('</script>', $output);
        $this->assertStringContainsString('"@context": "https://schema.org"', $output);
        $this->assertStringContainsString('"@type": "Organization"', $output);
        $this->assertStringNotContainsString('@graph', $output);
    }

    public function testRenderMultipleSchemasUsesGraph(): void
    {
        $manager = new SchemaManager();
        $manager->add((new SchemaType('Organization'))->set('name', 'Acme'));
        $manager->add((new SchemaType('WebSite'))->set('name', 'My Site'));

        $output = $manager->render();

        $this->assertStringContainsString('@graph', $output);

        // Verify the JSON is valid
        preg_match('/<script type="application\/ld\+json">\n(.+)\n<\/script>/s', $output, $matches);
        $this->assertNotEmpty($matches);

        $decoded = json_decode($matches[1], true);
        $this->assertSame('https://schema.org', $decoded['@context']);
        $this->assertCount(2, $decoded['@graph']);
    }

    public function testRenderProducesValidJson(): void
    {
        $manager = new SchemaManager();
        $manager->add(
            (new SchemaType('Organization'))
                ->set('name', 'Acme Corp')
                ->set('url', 'https://acme.com/path?q=1&b=2')
        );

        $output = $manager->render();

        preg_match('/<script type="application\/ld\+json">\n(.+)\n<\/script>/s', $output, $matches);
        $decoded = json_decode($matches[1], true);

        $this->assertNotNull($decoded);
        $this->assertSame('https://acme.com/path?q=1&b=2', $decoded['url']);
    }
}
