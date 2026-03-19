<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\QueryContextInterface;
use BackTo\Framework\Seo\Hooks\RegisterDefaultSchemas;
use BackTo\Framework\Seo\Contracts\BreadcrumbSchemaGeneratorInterface;
use BackTo\Framework\Seo\Schema\Generator\OrganizationSchemaGenerator;
use BackTo\Framework\Seo\Schema\Generator\PostTypeSchemaResolver;
use BackTo\Framework\Seo\Schema\Generator\WebSiteSchemaGenerator;
use BackTo\Framework\Seo\Schema\SchemaManager;
use BackTo\Framework\Seo\Schema\SchemaType;
use PHPUnit\Framework\TestCase;

class RegisterDefaultSchemasTest extends TestCase
{
    private HookDispatcherInterface $hookDispatcher;
    private QueryContextInterface $queryContext;

    protected function setUp(): void
    {
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->queryContext = $this->createMock(QueryContextInterface::class);
        $this->queryContext->method('isSingular')->willReturn(false);
    }

    private function createHook(
        ?SchemaManager $manager = null,
        ?WebSiteSchemaGenerator $webSiteGen = null,
        ?OrganizationSchemaGenerator $orgGen = null,
        ?BreadcrumbSchemaGeneratorInterface $breadcrumbGen = null,
    ): RegisterDefaultSchemas {
        $webSiteGen ??= $this->createMock(WebSiteSchemaGenerator::class);
        $webSiteGen->method('generate')->willReturn(new SchemaType('WebSite'));

        $orgGen ??= $this->createMock(OrganizationSchemaGenerator::class);
        $orgGen->method('generate')->willReturn(new SchemaType('Organization'));

        $breadcrumbGen ??= $this->createMock(BreadcrumbSchemaGeneratorInterface::class);
        $breadcrumbGen->method('generate')->willReturn(null);

        return new RegisterDefaultSchemas(
            $manager ?? new SchemaManager(),
            $this->hookDispatcher,
            $this->queryContext,
            $webSiteGen,
            $orgGen,
            new PostTypeSchemaResolver(),
            $breadcrumbGen,
        );
    }

    public function testRegistersOnWpHook(): void
    {
        $this->hookDispatcher->expects($this->once())
            ->method('addAction')
            ->with('wp', $this->anything());

        $this->createHook()->hooks();
    }

    public function testRegistersWebSiteAndOrganizationAlways(): void
    {
        $manager = new SchemaManager();

        $webSiteGen = $this->createMock(WebSiteSchemaGenerator::class);
        $webSiteGen->expects($this->once())->method('generate')
            ->willReturn(new SchemaType('WebSite'));

        $orgGen = $this->createMock(OrganizationSchemaGenerator::class);
        $orgGen->expects($this->once())->method('generate')
            ->willReturn(new SchemaType('Organization'));

        $hook = $this->createHook($manager, $webSiteGen, $orgGen);
        $hook->register();

        $this->assertCount(2, $manager->getSchemas());
        $this->assertSame('WebSite', $manager->getSchemas()[0]->getType());
        $this->assertSame('Organization', $manager->getSchemas()[1]->getType());
    }

    public function testRegistersBreadcrumbWhenAvailable(): void
    {
        $manager = new SchemaManager();

        $breadcrumbGen = $this->createMock(BreadcrumbSchemaGeneratorInterface::class);
        $breadcrumbGen->method('generate')->willReturn(new SchemaType('BreadcrumbList'));

        $hook = $this->createHook($manager, breadcrumbGen: $breadcrumbGen);
        $hook->register();

        $this->assertCount(3, $manager->getSchemas());
        $this->assertSame('BreadcrumbList', $manager->getSchemas()[2]->getType());
    }

    public function testSkipsNullBreadcrumb(): void
    {
        $manager = new SchemaManager();

        $hook = $this->createHook($manager);
        $hook->register();

        $this->assertCount(2, $manager->getSchemas());
    }
}
