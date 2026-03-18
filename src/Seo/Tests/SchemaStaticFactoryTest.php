<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Tests;

use BackTo\Framework\Seo\Schema;
use BackTo\Framework\Seo\Schema\SchemaRef;
use BackTo\Framework\Seo\Schema\SchemaType;
use BackTo\Framework\Seo\Schema\Type\Article;
use BackTo\Framework\Seo\Schema\Type\BreadcrumbList;
use BackTo\Framework\Seo\Schema\Type\Event;
use BackTo\Framework\Seo\Schema\Type\FAQPage;
use BackTo\Framework\Seo\Schema\Type\JobPosting;
use BackTo\Framework\Seo\Schema\Type\Organization;
use BackTo\Framework\Seo\Schema\Type\Person;
use BackTo\Framework\Seo\Schema\Type\Product;
use BackTo\Framework\Seo\Schema\Type\WebSite;
use PHPUnit\Framework\TestCase;

class SchemaStaticFactoryTest extends TestCase
{
    public function testOrganization(): void
    {
        $this->assertInstanceOf(Organization::class, Schema::organization());
    }

    public function testArticle(): void
    {
        $this->assertInstanceOf(Article::class, Schema::article());
    }

    public function testBreadcrumbList(): void
    {
        $this->assertInstanceOf(BreadcrumbList::class, Schema::breadcrumbList());
    }

    public function testPerson(): void
    {
        $this->assertInstanceOf(Person::class, Schema::person());
    }

    public function testWebSite(): void
    {
        $this->assertInstanceOf(WebSite::class, Schema::webSite());
    }

    public function testEvent(): void
    {
        $this->assertInstanceOf(Event::class, Schema::event());
    }

    public function testProduct(): void
    {
        $this->assertInstanceOf(Product::class, Schema::product());
    }

    public function testFaqPage(): void
    {
        $this->assertInstanceOf(FAQPage::class, Schema::faqPage());
    }

    public function testJobPosting(): void
    {
        $this->assertInstanceOf(JobPosting::class, Schema::jobPosting());
    }

    public function testType(): void
    {
        $type = Schema::type('CustomType');
        $this->assertInstanceOf(SchemaType::class, $type);
    }

    public function testRef(): void
    {
        $ref = Schema::ref('#organization');
        $this->assertInstanceOf(SchemaRef::class, $ref);
    }
}
