<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
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
    public function testRegistersOnWpHook(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('addAction')
            ->with('wp', $this->anything());

        $hook = new RegisterDefaultSchemas(
            new SchemaManager(),
            $dispatcher,
            $this->createMock(WebSiteSchemaGenerator::class),
            $this->createMock(OrganizationSchemaGenerator::class),
            new PostTypeSchemaResolver(),
            $this->createMock(BreadcrumbSchemaGeneratorInterface::class),
        );

        $hook->hooks();
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

        $breadcrumbGen = $this->createMock(BreadcrumbSchemaGeneratorInterface::class);
        $breadcrumbGen->method('generate')->willReturn(null);

        $hook = new RegisterDefaultSchemas(
            $manager,
            $this->createMock(HookDispatcherInterface::class),
            $webSiteGen,
            $orgGen,
            new PostTypeSchemaResolver(),
            $breadcrumbGen,
        );

        $hook->register();

        $this->assertCount(2, $manager->getSchemas());
        $this->assertSame('WebSite', $manager->getSchemas()[0]->getType());
        $this->assertSame('Organization', $manager->getSchemas()[1]->getType());
    }

    public function testRegistersBreadcrumbWhenAvailable(): void
    {
        $manager = new SchemaManager();

        $webSiteGen = $this->createMock(WebSiteSchemaGenerator::class);
        $webSiteGen->method('generate')->willReturn(new SchemaType('WebSite'));

        $orgGen = $this->createMock(OrganizationSchemaGenerator::class);
        $orgGen->method('generate')->willReturn(new SchemaType('Organization'));

        $breadcrumbGen = $this->createMock(BreadcrumbSchemaGeneratorInterface::class);
        $breadcrumbGen->method('generate')->willReturn(new SchemaType('BreadcrumbList'));

        $hook = new RegisterDefaultSchemas(
            $manager,
            $this->createMock(HookDispatcherInterface::class),
            $webSiteGen,
            $orgGen,
            new PostTypeSchemaResolver(),
            $breadcrumbGen,
        );

        $hook->register();

        $this->assertCount(3, $manager->getSchemas());
        $this->assertSame('BreadcrumbList', $manager->getSchemas()[2]->getType());
    }

    public function testSkipsNullBreadcrumb(): void
    {
        $manager = new SchemaManager();

        $webSiteGen = $this->createMock(WebSiteSchemaGenerator::class);
        $webSiteGen->method('generate')->willReturn(new SchemaType('WebSite'));

        $orgGen = $this->createMock(OrganizationSchemaGenerator::class);
        $orgGen->method('generate')->willReturn(new SchemaType('Organization'));

        $breadcrumbGen = $this->createMock(BreadcrumbSchemaGeneratorInterface::class);
        $breadcrumbGen->method('generate')->willReturn(null);

        $hook = new RegisterDefaultSchemas(
            $manager,
            $this->createMock(HookDispatcherInterface::class),
            $webSiteGen,
            $orgGen,
            new PostTypeSchemaResolver(),
            $breadcrumbGen,
        );

        $hook->register();

        $this->assertCount(2, $manager->getSchemas());
    }
}
