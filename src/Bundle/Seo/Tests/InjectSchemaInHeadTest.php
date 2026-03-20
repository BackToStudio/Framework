<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Bundle\Seo\Hooks\InjectSchemaInHead;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;
use BackTo\Framework\Bundle\Seo\Schema\SchemaType;
use PHPUnit\Framework\TestCase;

class InjectSchemaInHeadTest extends TestCase
{
    private HookDispatcherInterface $hookDispatcher;

    protected function setUp(): void
    {
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
    }

    public function testHooksRegistersWpHeadAction(): void
    {
        $manager = new SchemaManager();
        $injector = new InjectSchemaInHead($manager, $this->hookDispatcher);

        $this->hookDispatcher->expects($this->once())
            ->method('addAction')
            ->with('wp_head', [$injector, 'render'], 1);

        $injector->hooks();
    }

    public function testRenderOutputsSchemaJson(): void
    {
        $manager = new SchemaManager();
        $manager->add((new SchemaType('Organization'))->set('name', 'Acme'));

        $injector = new InjectSchemaInHead($manager, $this->hookDispatcher);

        ob_start();
        $injector->render();
        $output = ob_get_clean();

        $this->assertStringContainsString('application/ld+json', $output);
        $this->assertStringContainsString('Organization', $output);
        $this->assertStringContainsString('Acme', $output);
    }

    public function testRenderOutputsNothingWhenNoSchemas(): void
    {
        $manager = new SchemaManager();
        $injector = new InjectSchemaInHead($manager, $this->hookDispatcher);

        ob_start();
        $injector->render();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }
}
