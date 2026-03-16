<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Tests;

use BackTo\Framework\Seo\Schema;
use BackTo\Framework\Seo\Schema\SchemaType;
use BackTo\Framework\Seo\Schema\Type\Article;
use BackTo\Framework\Seo\Schema\Type\BreadcrumbList;
use BackTo\Framework\Seo\Schema\Type\ImageObject;
use BackTo\Framework\Seo\Schema\Type\ListItem;
use BackTo\Framework\Seo\Schema\Type\LocalBusiness;
use BackTo\Framework\Seo\Schema\Type\Organization;
use BackTo\Framework\Seo\Schema\Type\Person;
use BackTo\Framework\Seo\Schema\Type\PostalAddress;
use BackTo\Framework\Seo\Schema\Type\SearchAction;
use BackTo\Framework\Seo\Schema\Type\WebSite;
use PHPUnit\Framework\TestCase;

class SchemaFactoryTest extends TestCase
{
    public function testOrganization(): void
    {
        $schema = Schema::organization();
        $this->assertInstanceOf(Organization::class, $schema);
        $this->assertSame('Organization', $schema->getType());
    }

    public function testLocalBusiness(): void
    {
        $schema = Schema::localBusiness();
        $this->assertInstanceOf(LocalBusiness::class, $schema);
        $this->assertSame('LocalBusiness', $schema->getType());
    }

    public function testArticle(): void
    {
        $schema = Schema::article();
        $this->assertInstanceOf(Article::class, $schema);
        $this->assertSame('Article', $schema->getType());
    }

    public function testBreadcrumbList(): void
    {
        $schema = Schema::breadcrumbList();
        $this->assertInstanceOf(BreadcrumbList::class, $schema);
        $this->assertSame('BreadcrumbList', $schema->getType());
    }

    public function testListItem(): void
    {
        $schema = Schema::listItem();
        $this->assertInstanceOf(ListItem::class, $schema);
        $this->assertSame('ListItem', $schema->getType());
    }

    public function testPerson(): void
    {
        $schema = Schema::person();
        $this->assertInstanceOf(Person::class, $schema);
        $this->assertSame('Person', $schema->getType());
    }

    public function testPostalAddress(): void
    {
        $schema = Schema::postalAddress();
        $this->assertInstanceOf(PostalAddress::class, $schema);
        $this->assertSame('PostalAddress', $schema->getType());
    }

    public function testWebSite(): void
    {
        $schema = Schema::webSite();
        $this->assertInstanceOf(WebSite::class, $schema);
        $this->assertSame('WebSite', $schema->getType());
    }

    public function testImageObject(): void
    {
        $schema = Schema::imageObject();
        $this->assertInstanceOf(ImageObject::class, $schema);
        $this->assertSame('ImageObject', $schema->getType());
    }

    public function testSearchAction(): void
    {
        $schema = Schema::searchAction();
        $this->assertInstanceOf(SearchAction::class, $schema);
        $this->assertSame('SearchAction', $schema->getType());
    }

    public function testGenericType(): void
    {
        $schema = Schema::type('Product');
        $this->assertInstanceOf(SchemaType::class, $schema);
        $this->assertSame('Product', $schema->getType());
    }

    public function testFluentComposition(): void
    {
        $schema = Schema::organization()
            ->name('Acme Corp')
            ->url('https://acme.com')
            ->logo('https://acme.com/logo.png')
            ->address(
                Schema::postalAddress()
                    ->streetAddress('123 Main St')
                    ->addressLocality('Paris')
                    ->postalCode('75001')
                    ->addressCountry('FR')
            )
            ->sameAs(['https://facebook.com/acme', 'https://twitter.com/acme']);

        $array = $schema->toArray();

        $this->assertSame('Organization', $array['@type']);
        $this->assertSame('Acme Corp', $array['name']);
        $this->assertSame('PostalAddress', $array['address']['@type']);
        $this->assertSame('Paris', $array['address']['addressLocality']);
        $this->assertCount(2, $array['sameAs']);
    }

    public function testBreadcrumbComposition(): void
    {
        $schema = Schema::breadcrumbList()->items([
            Schema::listItem()->position(1)->name('Accueil')->url('/'),
            Schema::listItem()->position(2)->name('Blog')->url('/blog'),
            Schema::listItem()->position(3)->name('Article')->url('/blog/article'),
        ]);

        $array = $schema->toArray();

        $this->assertSame('BreadcrumbList', $array['@type']);
        $this->assertCount(3, $array['itemListElement']);
        $this->assertSame(2, $array['itemListElement'][1]['position']);
        $this->assertSame('/blog', $array['itemListElement'][1]['item']);
    }

    public function testArticleWithNestedTypes(): void
    {
        $schema = Schema::article()
            ->headline('Mon article')
            ->author(Schema::person()->name('Jean Dupont'))
            ->publisher(
                Schema::organization()
                    ->name('Acme')
                    ->logo(Schema::imageObject()->url('https://acme.com/logo.png')->width(600)->height(60))
            )
            ->datePublished('2026-03-16')
            ->dateModified('2026-03-16');

        $array = $schema->toArray();

        $this->assertSame('Article', $array['@type']);
        $this->assertSame('Person', $array['author']['@type']);
        $this->assertSame('Jean Dupont', $array['author']['name']);
        $this->assertSame('Organization', $array['publisher']['@type']);
        $this->assertSame('ImageObject', $array['publisher']['logo']['@type']);
    }
}
