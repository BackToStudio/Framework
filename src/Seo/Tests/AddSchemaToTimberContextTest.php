<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Seo\Hooks\AddSchemaToTimberContext;
use BackTo\Framework\Seo\Schema\SchemaManager;
use PHPUnit\Framework\TestCase;

class AddSchemaToTimberContextTest extends TestCase
{
    private HookDispatcherInterface $hookDispatcher;

    protected function setUp(): void
    {
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
    }

    public function testHooksRegistersTimberContextFilter(): void
    {
        $manager = new SchemaManager();
        $hook = new AddSchemaToTimberContext($manager, $this->hookDispatcher);

        $this->hookDispatcher->expects($this->once())
            ->method('addFilter')
            ->with('timber/context', [$hook, 'addSchema']);

        $hook->hooks();
    }

    public function testAddSchemaAddsManagerToContext(): void
    {
        $manager = new SchemaManager();
        $hook = new AddSchemaToTimberContext($manager, $this->hookDispatcher);

        $context = ['site' => 'test'];
        $result = $hook->addSchema($context);

        $this->assertSame($manager, $result['schema']);
        $this->assertSame('test', $result['site']);
    }
}
