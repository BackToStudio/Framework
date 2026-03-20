<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Theme\Tests;

use BackTo\Framework\Compose\AbstractKernel;
use BackTo\Framework\Bundle\Theme\ThemeKernel;
use PHPUnit\Framework\TestCase;

class ThemeKernelTest extends TestCase
{
    public function testExtendsAbstractKernel(): void
    {
        $kernel = new ThemeKernel('test', false);

        $this->assertInstanceOf(AbstractKernel::class, $kernel);
    }

    public function testGetDirectoryParameterName(): void
    {
        $kernel = new ThemeKernel('test', false);

        $reflection = new \ReflectionMethod($kernel, 'getDirectoryParameterName');
        $reflection->setAccessible(true);

        $this->assertSame('themeDirectory', $reflection->invoke($kernel));
    }

    public function testGetTextDomainParameterName(): void
    {
        $kernel = new ThemeKernel('test', false);

        $reflection = new \ReflectionMethod($kernel, 'getTextDomainParameterName');
        $reflection->setAccessible(true);

        $this->assertSame('themeTextDomain', $reflection->invoke($kernel));
    }

    public function testGetKernelConfigDirReturnsValidPath(): void
    {
        $kernel = new ThemeKernel('test', false);

        $reflection = new \ReflectionMethod($kernel, 'getKernelConfigDir');
        $reflection->setAccessible(true);

        $configDir = $reflection->invoke($kernel);
        $this->assertStringEndsWith('Resources/config', $configDir);
        $this->assertDirectoryExists($configDir);
    }

    public function testParameterNamesAreDistinctFromPluginKernel(): void
    {
        $kernel = new ThemeKernel('test', false);
        $dirMethod = new \ReflectionMethod($kernel, 'getDirectoryParameterName');
        $dirMethod->setAccessible(true);
        $domainMethod = new \ReflectionMethod($kernel, 'getTextDomainParameterName');
        $domainMethod->setAccessible(true);

        $this->assertStringStartsWith('theme', $dirMethod->invoke($kernel));
        $this->assertStringStartsWith('theme', $domainMethod->invoke($kernel));
    }
}
