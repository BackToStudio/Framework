<?php

declare(strict_types=1);

namespace BackTo\Framework\Plugin\Tests;

use BackTo\Framework\Compose\AbstractKernel;
use BackTo\Framework\Plugin\PluginKernel;
use PHPUnit\Framework\TestCase;

class PluginKernelTest extends TestCase
{
    public function testExtendsAbstractKernel(): void
    {
        $kernel = new PluginKernel('test', false);

        $this->assertInstanceOf(AbstractKernel::class, $kernel);
    }

    public function testGetDirectoryParameterName(): void
    {
        $kernel = new PluginKernel('test', false);

        $reflection = new \ReflectionMethod($kernel, 'getDirectoryParameterName');
        $reflection->setAccessible(true);

        $this->assertSame('pluginDirectory', $reflection->invoke($kernel));
    }

    public function testGetTextDomainParameterName(): void
    {
        $kernel = new PluginKernel('test', false);

        $reflection = new \ReflectionMethod($kernel, 'getTextDomainParameterName');
        $reflection->setAccessible(true);

        $this->assertSame('pluginTextDomain', $reflection->invoke($kernel));
    }

    public function testGetKernelConfigDirReturnsValidPath(): void
    {
        $kernel = new PluginKernel('test', false);

        $reflection = new \ReflectionMethod($kernel, 'getKernelConfigDir');
        $reflection->setAccessible(true);

        $configDir = $reflection->invoke($kernel);
        $this->assertStringEndsWith('Resources/config', $configDir);
        $this->assertDirectoryExists($configDir);
    }

    public function testKernelWithDebugMode(): void
    {
        $kernel = new PluginKernel('production', true);

        $this->assertInstanceOf(AbstractKernel::class, $kernel);
    }

    public function testParameterNamesAreDistinctFromThemeKernel(): void
    {
        $kernel = new PluginKernel('test', false);
        $dirMethod = new \ReflectionMethod($kernel, 'getDirectoryParameterName');
        $dirMethod->setAccessible(true);
        $domainMethod = new \ReflectionMethod($kernel, 'getTextDomainParameterName');
        $domainMethod->setAccessible(true);

        // Plugin params must not conflict with theme params
        $this->assertStringStartsWith('plugin', $dirMethod->invoke($kernel));
        $this->assertStringStartsWith('plugin', $domainMethod->invoke($kernel));
    }
}
