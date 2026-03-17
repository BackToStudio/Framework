<?php

declare(strict_types=1);

namespace BackTo\Framework\Plugin\Tests;

require_once __DIR__ . '/wp_stubs.php';

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Plugin\I18n\LoadPluginTextDomain;
use PHPUnit\Framework\TestCase;

class LoadPluginTextDomainTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['_load_plugin_textdomain_calls'] = [];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_load_plugin_textdomain_calls']);
    }

    public function testImplementsHooksInterface(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $loader = new LoadPluginTextDomain('/path/to/plugin', 'my-plugin', $dispatcher);

        $this->assertInstanceOf(Hooks::class, $loader);
    }

    public function testHooksRegistersInitAction(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('addAction')
            ->with('init', $this->anything());

        $loader = new LoadPluginTextDomain('/path/to/plugin', 'my-plugin', $dispatcher);
        $loader->hooks();
    }

    public function testLoadTranslationsPassesCorrectTextDomain(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $loader = new LoadPluginTextDomain('/var/www/wp-content/plugins/my-plugin', 'my-plugin', $dispatcher);

        $loader->loadTranslations();

        $this->assertCount(1, $GLOBALS['_load_plugin_textdomain_calls']);
        $this->assertSame('my-plugin', $GLOBALS['_load_plugin_textdomain_calls'][0]['domain']);
    }

    public function testLoadTranslationsBuildsCorrectLanguagePath(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $loader = new LoadPluginTextDomain('/var/www/wp-content/plugins/my-plugin', 'my-plugin', $dispatcher);

        $loader->loadTranslations();

        $expectedPath = 'my-plugin' . DIRECTORY_SEPARATOR . 'languages';
        $this->assertSame($expectedPath, $GLOBALS['_load_plugin_textdomain_calls'][0]['path']);
    }

    public function testLoadTranslationsWithNestedDirectoryPath(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $loader = new LoadPluginTextDomain('/var/www/wp-content/plugins/vendor/my-plugin', 'my-plugin', $dispatcher);

        $loader->loadTranslations();

        // basename() should only take the last directory segment
        $expectedPath = 'my-plugin' . DIRECTORY_SEPARATOR . 'languages';
        $this->assertSame($expectedPath, $GLOBALS['_load_plugin_textdomain_calls'][0]['path']);
    }

    public function testLoadTranslationsWithTrailingSlash(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        // basename('/path/to/plugin/') returns '' on some PHP versions
        $loader = new LoadPluginTextDomain('/path/to/plugin/', 'test-domain', $dispatcher);

        $loader->loadTranslations();

        // Verify the function was still called even with trailing slash
        $this->assertCount(1, $GLOBALS['_load_plugin_textdomain_calls']);
        $this->assertSame('test-domain', $GLOBALS['_load_plugin_textdomain_calls'][0]['domain']);
    }

    public function testLoadTranslationsPassesFalseAsDeprecatedParam(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $loader = new LoadPluginTextDomain('/path/to/plugin', 'my-plugin', $dispatcher);

        $loader->loadTranslations();

        $this->assertFalse($GLOBALS['_load_plugin_textdomain_calls'][0]['deprecated']);
    }

    public function testHooksCallbackIsCallableArray(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('addAction')
            ->with('init', $this->callback(function ($callback) {
                return is_array($callback) && $callback[1] === 'loadTranslations';
            }));

        $loader = new LoadPluginTextDomain('/path', 'domain', $dispatcher);
        $loader->hooks();
    }
}
