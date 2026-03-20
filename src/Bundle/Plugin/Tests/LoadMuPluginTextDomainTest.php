<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Plugin\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Plugin\Contracts\TextDomainLoaderInterface;
use BackTo\Framework\Bundle\Plugin\I18n\LoadMuPluginTextDomain;
use PHPUnit\Framework\TestCase;

class LoadMuPluginTextDomainTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private TextDomainLoaderInterface $textDomainLoader;
    private array $loadCalls;

    protected function setUp(): void
    {
        $this->loadCalls = [];
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->textDomainLoader = $this->createMock(TextDomainLoaderInterface::class);
        $this->textDomainLoader->method('loadMuPluginTextDomain')
            ->willReturnCallback(function (string $domain, string $path) {
                $this->loadCalls[] = ['domain' => $domain, 'path' => $path];
            });
    }

    public function testImplementsHooksInterface(): void
    {
        $loader = new LoadMuPluginTextDomain('/path/to/mu-plugin', 'my-plugin', $this->dispatcher, $this->textDomainLoader);

        $this->assertInstanceOf(Hooks::class, $loader);
    }

    public function testHooksRegistersInitAction(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('addAction')
            ->with('init', $this->anything());

        $loader = new LoadMuPluginTextDomain('/path/to/mu-plugin', 'my-plugin', $this->dispatcher, $this->textDomainLoader);
        $loader->hooks();
    }

    public function testLoadTranslationsPassesCorrectDomain(): void
    {
        $loader = new LoadMuPluginTextDomain('/var/www/wp-content/mu-plugins/my-plugin', 'my-mu-plugin', $this->dispatcher, $this->textDomainLoader);

        $loader->loadTranslations();

        $this->assertCount(1, $this->loadCalls);
        $this->assertSame('my-mu-plugin', $this->loadCalls[0]['domain']);
    }

    public function testLoadTranslationsBuildsCorrectPath(): void
    {
        $loader = new LoadMuPluginTextDomain('/var/www/wp-content/mu-plugins/my-plugin', 'my-plugin', $this->dispatcher, $this->textDomainLoader);

        $loader->loadTranslations();

        $expectedPath = 'my-plugin' . DIRECTORY_SEPARATOR . 'languages';
        $this->assertSame($expectedPath, $this->loadCalls[0]['path']);
    }

    public function testLoadTranslationsWithNestedPath(): void
    {
        $loader = new LoadMuPluginTextDomain('/deeply/nested/mu-plugins/custom', 'custom', $this->dispatcher, $this->textDomainLoader);

        $loader->loadTranslations();

        $expectedPath = 'custom' . DIRECTORY_SEPARATOR . 'languages';
        $this->assertSame($expectedPath, $this->loadCalls[0]['path']);
    }
}
