<?php

declare(strict_types=1);

namespace BackTo\Framework\Plugin\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Plugin\Contracts\TextDomainLoaderInterface;
use BackTo\Framework\Plugin\I18n\LoadPluginTextDomain;
use PHPUnit\Framework\TestCase;

class LoadPluginTextDomainTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private TextDomainLoaderInterface $textDomainLoader;
    private array $loadCalls;

    protected function setUp(): void
    {
        $this->loadCalls = [];
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->textDomainLoader = $this->createMock(TextDomainLoaderInterface::class);
        $this->textDomainLoader->method('loadPluginTextDomain')
            ->willReturnCallback(function (string $domain, string $path) {
                $this->loadCalls[] = ['domain' => $domain, 'path' => $path];
            });
    }

    public function testImplementsHooksInterface(): void
    {
        $loader = new LoadPluginTextDomain('/path/to/plugin', 'my-plugin', $this->dispatcher, $this->textDomainLoader);

        $this->assertInstanceOf(Hooks::class, $loader);
    }

    public function testHooksRegistersInitAction(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('addAction')
            ->with('init', $this->anything());

        $loader = new LoadPluginTextDomain('/path/to/plugin', 'my-plugin', $this->dispatcher, $this->textDomainLoader);
        $loader->hooks();
    }

    public function testLoadTranslationsPassesCorrectTextDomain(): void
    {
        $loader = new LoadPluginTextDomain('/var/www/wp-content/plugins/my-plugin', 'my-plugin', $this->dispatcher, $this->textDomainLoader);

        $loader->loadTranslations();

        $this->assertCount(1, $this->loadCalls);
        $this->assertSame('my-plugin', $this->loadCalls[0]['domain']);
    }

    public function testLoadTranslationsBuildsCorrectLanguagePath(): void
    {
        $loader = new LoadPluginTextDomain('/var/www/wp-content/plugins/my-plugin', 'my-plugin', $this->dispatcher, $this->textDomainLoader);

        $loader->loadTranslations();

        $expectedPath = 'my-plugin' . DIRECTORY_SEPARATOR . 'languages';
        $this->assertSame($expectedPath, $this->loadCalls[0]['path']);
    }

    public function testHooksCallbackIsCallableArray(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('addAction')
            ->with('init', $this->callback(function ($callback) {
                return is_array($callback) && $callback[1] === 'loadTranslations';
            }));

        $loader = new LoadPluginTextDomain('/path', 'domain', $this->dispatcher, $this->textDomainLoader);
        $loader->hooks();
    }
}
