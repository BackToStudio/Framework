<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Theme\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Plugin\Contracts\TextDomainLoaderInterface;
use BackTo\Framework\Bundle\Theme\I18n\LoadThemeTextDomain;
use PHPUnit\Framework\TestCase;

class LoadThemeTextDomainTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private TextDomainLoaderInterface $textDomainLoader;
    private array $loadCalls;

    protected function setUp(): void
    {
        $this->loadCalls = [];
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->textDomainLoader = $this->createMock(TextDomainLoaderInterface::class);
        $this->textDomainLoader->method('loadThemeTextDomain')
            ->willReturnCallback(function (string $domain, string $path) {
                $this->loadCalls[] = ['domain' => $domain, 'path' => $path];
            });
    }

    public function testImplementsHooksInterface(): void
    {
        $loader = new LoadThemeTextDomain('/path/to/theme', 'my-theme', $this->dispatcher, $this->textDomainLoader);

        $this->assertInstanceOf(Hooks::class, $loader);
    }

    public function testHooksRegistersAfterSetupThemeAction(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('addAction')
            ->with('after_setup_theme', $this->anything());

        $loader = new LoadThemeTextDomain('/path/to/theme', 'my-theme', $this->dispatcher, $this->textDomainLoader);
        $loader->hooks();
    }

    public function testLoadTranslationsPassesCorrectDomain(): void
    {
        $loader = new LoadThemeTextDomain('/var/www/themes/my-theme', 'my-custom-theme', $this->dispatcher, $this->textDomainLoader);

        $loader->loadTranslations();

        $this->assertCount(1, $this->loadCalls);
        $this->assertSame('my-custom-theme', $this->loadCalls[0]['domain']);
    }

    public function testLoadTranslationsUsesFullDirectoryPathWithLanguagesSuffix(): void
    {
        $themeDir = '/var/www/wp-content/themes/my-theme';
        $loader = new LoadThemeTextDomain($themeDir, 'my-theme', $this->dispatcher, $this->textDomainLoader);

        $loader->loadTranslations();

        $expectedPath = $themeDir . DIRECTORY_SEPARATOR . 'languages';
        $this->assertSame($expectedPath, $this->loadCalls[0]['path']);
    }

    public function testThemeUsesFullPathWhilePluginUsesBasename(): void
    {
        $themeDir = '/var/www/wp-content/themes/my-theme';
        $loader = new LoadThemeTextDomain($themeDir, 'my-theme', $this->dispatcher, $this->textDomainLoader);

        $loader->loadTranslations();

        $path = $this->loadCalls[0]['path'];
        $this->assertStringStartsWith('/', $path);
    }
}
