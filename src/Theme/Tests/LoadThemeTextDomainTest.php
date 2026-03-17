<?php

declare(strict_types=1);

namespace BackTo\Framework\Theme\Tests;

require_once __DIR__ . '/wp_stubs.php';

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Theme\I18n\LoadThemeTextDomain;
use PHPUnit\Framework\TestCase;

class LoadThemeTextDomainTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['_load_theme_textdomain_calls'] = [];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_load_theme_textdomain_calls']);
    }

    public function testImplementsHooksInterface(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $loader = new LoadThemeTextDomain('/path/to/theme', 'my-theme', $dispatcher);

        $this->assertInstanceOf(Hooks::class, $loader);
    }

    public function testHooksRegistersAfterSetupThemeAction(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('addAction')
            ->with('after_setup_theme', $this->anything());

        $loader = new LoadThemeTextDomain('/path/to/theme', 'my-theme', $dispatcher);
        $loader->hooks();
    }

    public function testUsesAfterSetupThemeNotInit(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('addAction')
            ->with(
                $this->callback(fn(string $hook) => $hook === 'after_setup_theme'),
                $this->anything()
            );

        $loader = new LoadThemeTextDomain('/path', 'domain', $dispatcher);
        $loader->hooks();
    }

    public function testLoadTranslationsPassesCorrectDomain(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $loader = new LoadThemeTextDomain('/var/www/themes/my-theme', 'my-custom-theme', $dispatcher);

        $loader->loadTranslations();

        $this->assertCount(1, $GLOBALS['_load_theme_textdomain_calls']);
        $this->assertSame('my-custom-theme', $GLOBALS['_load_theme_textdomain_calls'][0]['domain']);
    }

    public function testLoadTranslationsUsesFullDirectoryPathWithLanguagesSuffix(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $themeDir = '/var/www/wp-content/themes/my-theme';
        $loader = new LoadThemeTextDomain($themeDir, 'my-theme', $dispatcher);

        $loader->loadTranslations();

        // Theme uses $themeDirectory . DIRECTORY_SEPARATOR . 'languages' (full path, not basename)
        $expectedPath = $themeDir . DIRECTORY_SEPARATOR . 'languages';
        $this->assertSame($expectedPath, $GLOBALS['_load_theme_textdomain_calls'][0]['path']);
    }

    public function testThemeUsesFullPathWhilePluginUsesBasename(): void
    {
        // This is a key difference: themes pass full directory path, plugins pass basename
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $themeDir = '/var/www/wp-content/themes/my-theme';
        $loader = new LoadThemeTextDomain($themeDir, 'my-theme', $dispatcher);

        $loader->loadTranslations();

        $path = $GLOBALS['_load_theme_textdomain_calls'][0]['path'];
        // The theme path should start with '/' (absolute), unlike plugin which starts with basename
        $this->assertStringStartsWith('/', $path);
    }
}
