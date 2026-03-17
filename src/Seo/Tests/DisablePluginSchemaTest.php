<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Seo\Actions\DisablePluginSchema;
use BackTo\Framework\Seo\Contracts\SeoProviderInterface;
use BackTo\Framework\Seo\SeoManager;
use PHPUnit\Framework\TestCase;

class DisablePluginSchemaTest extends TestCase
{
    public function testDoesNothingWhenNoProvider(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('addFilter');

        $manager = new SeoManager();
        $action = new DisablePluginSchema($manager, $dispatcher);
        $action->hooks();
    }

    public function testDisablesYoastSchema(): void
    {
        $provider = $this->createMock(SeoProviderInterface::class);
        $provider->method('isActive')->willReturn(true);
        $provider->method('getName')->willReturn('yoast');

        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('addFilter')
            ->with('wpseo_json_ld_output', '__return_empty_array');

        $manager = new SeoManager([$provider]);
        $action = new DisablePluginSchema($manager, $dispatcher);
        $action->hooks();
    }

    public function testDisablesSeoPressSchema(): void
    {
        $provider = $this->createMock(SeoProviderInterface::class);
        $provider->method('isActive')->willReturn(true);
        $provider->method('getName')->willReturn('seopress');

        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('addFilter')
            ->with('seopress_schemas_auto_enabled', '__return_false');

        $manager = new SeoManager([$provider]);
        $action = new DisablePluginSchema($manager, $dispatcher);
        $action->hooks();
    }

    public function testDoesNothingForUnknownProvider(): void
    {
        $provider = $this->createMock(SeoProviderInterface::class);
        $provider->method('isActive')->willReturn(true);
        $provider->method('getName')->willReturn('rank_math');

        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->never())->method('addFilter');

        $manager = new SeoManager([$provider]);
        $action = new DisablePluginSchema($manager, $dispatcher);
        $action->hooks();
    }
}
