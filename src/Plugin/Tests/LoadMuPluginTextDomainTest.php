<?php

declare(strict_types=1);

namespace BackTo\Framework\Plugin\Tests;

require_once __DIR__ . '/wp_stubs.php';

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Plugin\I18n\LoadMuPluginTextDomain;
use PHPUnit\Framework\TestCase;

class LoadMuPluginTextDomainTest extends TestCase
{
    protected function setUp(): void
    {
        $GLOBALS['_load_muplugin_textdomain_calls'] = [];
    }

    protected function tearDown(): void
    {
        unset($GLOBALS['_load_muplugin_textdomain_calls']);
    }

    public function testImplementsHooksInterface(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $loader = new LoadMuPluginTextDomain('/path/to/mu-plugin', 'my-plugin', $dispatcher);

        $this->assertInstanceOf(Hooks::class, $loader);
    }

    public function testHooksRegistersInitAction(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $dispatcher->expects($this->once())
            ->method('addAction')
            ->with('init', $this->anything());

        $loader = new LoadMuPluginTextDomain('/path/to/mu-plugin', 'my-plugin', $dispatcher);
        $loader->hooks();
    }

    public function testLoadTranslationsPassesCorrectDomain(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $loader = new LoadMuPluginTextDomain('/var/www/wp-content/mu-plugins/my-plugin', 'my-mu-plugin', $dispatcher);

        $loader->loadTranslations();

        $this->assertCount(1, $GLOBALS['_load_muplugin_textdomain_calls']);
        $this->assertSame('my-mu-plugin', $GLOBALS['_load_muplugin_textdomain_calls'][0]['domain']);
    }

    public function testLoadTranslationsBuildsCorrectPath(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $loader = new LoadMuPluginTextDomain('/var/www/wp-content/mu-plugins/my-plugin', 'my-plugin', $dispatcher);

        $loader->loadTranslations();

        // MU plugin uses basename() + DIRECTORY_SEPARATOR + 'languages'
        $expectedPath = 'my-plugin' . DIRECTORY_SEPARATOR . 'languages';
        $this->assertSame($expectedPath, $GLOBALS['_load_muplugin_textdomain_calls'][0]['path']);
    }

    public function testMuPluginDoesNotPassDeprecatedParam(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $loader = new LoadMuPluginTextDomain('/path', 'domain', $dispatcher);

        $loader->loadTranslations();

        // load_muplugin_textdomain only has 2 params (no deprecated param unlike load_plugin_textdomain)
        $call = $GLOBALS['_load_muplugin_textdomain_calls'][0];
        $this->assertArrayNotHasKey('deprecated', $call);
    }

    public function testLoadTranslationsWithNestedPath(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $loader = new LoadMuPluginTextDomain('/deeply/nested/mu-plugins/custom', 'custom', $dispatcher);

        $loader->loadTranslations();

        $expectedPath = 'custom' . DIRECTORY_SEPARATOR . 'languages';
        $this->assertSame($expectedPath, $GLOBALS['_load_muplugin_textdomain_calls'][0]['path']);
    }
}
