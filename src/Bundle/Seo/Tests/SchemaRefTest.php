<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Tests;

use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;
use BackTo\Framework\Bundle\Seo\Schema\SchemaRef;
use BackTo\Framework\Bundle\Seo\Schema\SchemaType;
use PHPUnit\Framework\TestCase;

class SchemaRefTest extends TestCase
{
    public function testRefFactory(): void
    {
        $ref = Schema::ref('#organization');
        $this->assertInstanceOf(SchemaRef::class, $ref);
    }

    public function testRefGetId(): void
    {
        $ref = new SchemaRef('#organization');
        $this->assertSame('#organization', $ref->getId());
    }

    public function testRefToArray(): void
    {
        $ref = new SchemaRef('https://example.com/#organization');
        $this->assertSame(['@id' => 'https://example.com/#organization'], $ref->toArray());
    }

    public function testRefJsonSerializable(): void
    {
        $ref = new SchemaRef('#website');
        $json = json_encode($ref);
        $decoded = json_decode($json, true);

        $this->assertSame(['@id' => '#website'], $decoded);
    }

    public function testSchemaTypeResolvesRef(): void
    {
        $article = new SchemaType('Article');
        $article->set('publisher', Schema::ref('#organization'));

        $array = $article->toArray();

        $this->assertSame(['@id' => '#organization'], $array['publisher']);
    }

    public function testSchemaTypeResolvesRefInArray(): void
    {
        $schema = new SchemaType('WebPage');
        $schema->set('isPartOf', [
            Schema::ref('#website'),
            Schema::ref('#organization'),
        ]);

        $array = $schema->toArray();

        $this->assertSame(['@id' => '#website'], $array['isPartOf'][0]);
        $this->assertSame(['@id' => '#organization'], $array['isPartOf'][1]);
    }

    public function testIdAndRefProduceLinkedGraph(): void
    {
        $manager = new SchemaManager();

        $org = Schema::organization()
            ->id('https://example.com/#organization')
            ->name('Acme');

        $website = Schema::webSite()
            ->id('https://example.com/#website')
            ->name('Acme Site')
            ->set('publisher', Schema::ref('https://example.com/#organization'));

        $article = Schema::article()
            ->headline('Test')
            ->author(Schema::person()->name('John'))
            ->datePublished('2026-01-01')
            ->set('isPartOf', Schema::ref('https://example.com/#website'))
            ->set('publisher', Schema::ref('https://example.com/#organization'));

        $manager->add($org);
        $manager->add($website);
        $manager->add($article);

        $output = $manager->render();

        // Parse JSON-LD
        preg_match('/<script type="application\/ld\+json">\n(.+)\n<\/script>/s', $output, $matches);
        $decoded = json_decode($matches[1], true);

        // Should use @graph for multiple schemas
        $this->assertSame('https://schema.org', $decoded['@context']);
        $this->assertCount(3, $decoded['@graph']);

        // Organization has @id
        $this->assertSame('https://example.com/#organization', $decoded['@graph'][0]['@id']);
        $this->assertSame('Organization', $decoded['@graph'][0]['@type']);

        // WebSite references Organization
        $this->assertSame('https://example.com/#website', $decoded['@graph'][1]['@id']);
        $this->assertSame(['@id' => 'https://example.com/#organization'], $decoded['@graph'][1]['publisher']);

        // Article references both
        $this->assertSame(['@id' => 'https://example.com/#website'], $decoded['@graph'][2]['isPartOf']);
        $this->assertSame(['@id' => 'https://example.com/#organization'], $decoded['@graph'][2]['publisher']);
    }
}
