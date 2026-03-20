<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Tests;

use BackTo\Framework\Bundle\Seo\Schema\SchemaType;
use PHPUnit\Framework\TestCase;

class SchemaTypeTest extends TestCase
{
    public function testGetType(): void
    {
        $schema = new SchemaType('Organization');
        $this->assertSame('Organization', $schema->getType());
    }

    public function testSetAndGetProperties(): void
    {
        $schema = new SchemaType('Person');
        $result = $schema->set('name', 'John Doe');

        $this->assertSame($schema, $result, 'set() should return $this for fluent chaining');
        $this->assertSame(['name' => 'John Doe'], $schema->getProperties());
    }

    public function testId(): void
    {
        $schema = new SchemaType('Organization');
        $schema->id('#org');

        $array = $schema->toArray();
        $this->assertSame('#org', $array['@id']);
    }

    public function testToArrayIncludesType(): void
    {
        $schema = new SchemaType('WebSite');
        $schema->set('name', 'My Site');

        $expected = [
            '@type' => 'WebSite',
            'name' => 'My Site',
        ];

        $this->assertSame($expected, $schema->toArray());
    }

    public function testNestedSchemaTypeIsResolved(): void
    {
        $address = new SchemaType('PostalAddress');
        $address->set('streetAddress', '123 Main St');

        $org = new SchemaType('Organization');
        $org->set('name', 'Acme');
        $org->set('address', $address);

        $array = $org->toArray();

        $this->assertSame([
            '@type' => 'Organization',
            'name' => 'Acme',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => '123 Main St',
            ],
        ], $array);
    }

    public function testArrayOfSchemaTypesIsResolved(): void
    {
        $item1 = new SchemaType('ListItem');
        $item1->set('position', 1)->set('name', 'Home');

        $item2 = new SchemaType('ListItem');
        $item2->set('position', 2)->set('name', 'Blog');

        $breadcrumb = new SchemaType('BreadcrumbList');
        $breadcrumb->set('itemListElement', [$item1, $item2]);

        $array = $breadcrumb->toArray();

        $this->assertCount(2, $array['itemListElement']);
        $this->assertSame('ListItem', $array['itemListElement'][0]['@type']);
        $this->assertSame('ListItem', $array['itemListElement'][1]['@type']);
    }

    public function testJsonSerializable(): void
    {
        $schema = new SchemaType('Person');
        $schema->set('name', 'Jane');

        $json = json_encode($schema);
        $decoded = json_decode($json, true);

        $this->assertSame('Person', $decoded['@type']);
        $this->assertSame('Jane', $decoded['name']);
    }

    public function testScalarValuesPassThrough(): void
    {
        $schema = new SchemaType('Article');
        $schema->set('wordCount', 1500);
        $schema->set('isAccessibleForFree', true);

        $array = $schema->toArray();

        $this->assertSame(1500, $array['wordCount']);
        $this->assertTrue($array['isAccessibleForFree']);
    }
}
