<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Performance\Hooks\AddResourceHints;
use PHPUnit\Framework\TestCase;

class AddResourceHintsTest extends TestCase
{
    private HookDispatcherInterface $hookDispatcher;

    protected function setUp(): void
    {
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
    }

    public function testHooksRegistersFilterOnly(): void
    {
        $hints = new AddResourceHints($this->hookDispatcher, ['https://fonts.gstatic.com']);

        $this->hookDispatcher->expects($this->once())
            ->method('addFilter')
            ->with('wp_resource_hints', [$hints, 'addHints'], 10, 2);

        $this->hookDispatcher->expects($this->never())
            ->method('addAction');

        $hints->hooks();
    }

    public function testHooksRegistersPreloadActionWhenPreloadResourcesProvided(): void
    {
        $preload = [['url' => '/style.css', 'as' => 'style']];
        $hints = new AddResourceHints($this->hookDispatcher, [], [], $preload);

        $this->hookDispatcher->expects($this->once())
            ->method('addFilter');

        $this->hookDispatcher->expects($this->once())
            ->method('addAction')
            ->with('wp_head', [$hints, 'addPreloadLinks'], 1);

        $hints->hooks();
    }

    public function testAddHintsPreconnect(): void
    {
        $hints = new AddResourceHints(
            $this->hookDispatcher,
            ['https://fonts.gstatic.com', 'https://cdn.example.com'],
        );

        $result = $hints->addHints([], 'preconnect');

        $this->assertSame(['https://fonts.gstatic.com', 'https://cdn.example.com'], $result);
    }

    public function testAddHintsDnsPrefetch(): void
    {
        $hints = new AddResourceHints(
            $this->hookDispatcher,
            [],
            ['https://analytics.example.com'],
        );

        $result = $hints->addHints([], 'dns-prefetch');

        $this->assertSame(['https://analytics.example.com'], $result);
    }

    public function testAddHintsPreservesExistingUrls(): void
    {
        $hints = new AddResourceHints(
            $this->hookDispatcher,
            ['https://new.example.com'],
        );

        $result = $hints->addHints(['https://existing.example.com'], 'preconnect');

        $this->assertSame(['https://existing.example.com', 'https://new.example.com'], $result);
    }

    public function testAddHintsIgnoresUnknownRelationType(): void
    {
        $hints = new AddResourceHints(
            $this->hookDispatcher,
            ['https://fonts.gstatic.com'],
            ['https://analytics.example.com'],
        );

        $result = $hints->addHints([], 'prefetch');

        $this->assertSame([], $result);
    }

    public function testAddPreloadLinksOutputsLinkTags(): void
    {
        if (!function_exists('esc_url')) {
            function_exists('esc_url') || eval('namespace { function esc_url($u) { return $u; } function esc_attr($a) { return $a; } }');
        }

        $preload = [
            ['url' => 'https://example.com/font.woff2', 'as' => 'font', 'type' => 'font/woff2'],
            ['url' => 'https://example.com/style.css', 'as' => 'style'],
        ];
        $hints = new AddResourceHints($this->hookDispatcher, [], [], $preload);

        ob_start();
        $hints->addPreloadLinks();
        $output = ob_get_clean();

        $this->assertStringContainsString('rel="preload"', $output);
        $this->assertStringContainsString('href="https://example.com/font.woff2"', $output);
        $this->assertStringContainsString('as="font"', $output);
        $this->assertStringContainsString('type="font/woff2"', $output);
        $this->assertStringContainsString('crossorigin', $output);
        $this->assertStringContainsString('href="https://example.com/style.css"', $output);
        $this->assertStringContainsString('as="style"', $output);
    }
}
