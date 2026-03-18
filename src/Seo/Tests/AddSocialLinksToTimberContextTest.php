<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Seo\Hooks\AddSocialLinksToTimberContext;
use BackTo\Framework\Seo\SeoManager;
use PHPUnit\Framework\TestCase;

class AddSocialLinksToTimberContextTest extends TestCase
{
    private HookDispatcherInterface $hookDispatcher;

    protected function setUp(): void
    {
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
    }

    public function testHooksRegistersTimberContextFilter(): void
    {
        $manager = new SeoManager();
        $hook = new AddSocialLinksToTimberContext($manager, $this->hookDispatcher);

        $this->hookDispatcher->expects($this->once())
            ->method('addFilter')
            ->with('timber/context', [$hook, 'addSocialLinks']);

        $hook->hooks();
    }

    public function testAddSocialLinksReturnsContextUnchangedWithoutProvider(): void
    {
        $manager = new SeoManager();
        $hook = new AddSocialLinksToTimberContext($manager, $this->hookDispatcher);

        $context = ['site' => 'test'];
        $result = $hook->addSocialLinks($context);

        $this->assertSame($context, $result);
    }
}
