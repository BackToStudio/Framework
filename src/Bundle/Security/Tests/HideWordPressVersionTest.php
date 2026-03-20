<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Bundle\Security\HideWordPressVersion;
use PHPUnit\Framework\TestCase;

class HideWordPressVersionTest extends TestCase
{
    public function testImplementsRequiredInterfaces(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new HideWordPressVersion($dispatcher);

        $this->assertInstanceOf(Hooks::class, $rule);
        $this->assertInstanceOf(SecurityRuleInterface::class, $rule);
    }

    public function testGetName(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new HideWordPressVersion($dispatcher);

        $this->assertSame('hide_wordpress_version', $rule->getName());
    }

    public function testHooksRegistersFiltersAndActions(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);

        $dispatcher->expects($this->exactly(3))
            ->method('addFilter')
            ->willReturnCallback(function (string $hook) {
                $this->assertContains($hook, ['the_generator', 'style_loader_src', 'script_loader_src']);
            });

        $dispatcher->expects($this->once())
            ->method('addAction')
            ->with('wp_head', $this->anything(), 1);

        $rule = new HideWordPressVersion($dispatcher);
        $rule->hooks();
    }

    public function testRemoveGenerator(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new HideWordPressVersion($dispatcher);

        $this->assertSame('', $rule->removeGenerator());
    }

    public function testRemoveVersionFromUrlWithVersion(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new HideWordPressVersion($dispatcher);

        $url = 'https://example.com/wp-includes/js/jquery.min.js?ver=3.6.0';
        $result = $rule->removeVersionFromUrl($url);

        $this->assertStringNotContainsString('ver=', $result);
    }

    public function testRemoveVersionFromUrlWithoutVersion(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new HideWordPressVersion($dispatcher);

        $url = 'https://example.com/wp-includes/js/jquery.min.js';
        $result = $rule->removeVersionFromUrl($url);

        $this->assertSame($url, $result);
    }
}
