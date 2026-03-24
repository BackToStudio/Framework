<?php

declare(strict_types=1);

namespace BackTo\Framework\OptionsResolver\Tests;

use BackTo\Framework\Contracts\ExtensionInterface;
use BackTo\Framework\OptionsResolver\Contracts\OptionsResolverInterface;
use BackTo\Framework\OptionsResolver\OptionsResolver;
use BackTo\Framework\OptionsResolver\OptionsResolverExtension;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BackTo\Framework\OptionsResolver\OptionsResolverExtension
 */
class OptionsResolverExtensionTest extends TestCase
{
    private OptionsResolverExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new OptionsResolverExtension();
        $this->container = new ContainerBuilder();
    }

    public function testImplementsExtensionInterface(): void
    {
        $this->assertInstanceOf(ExtensionInterface::class, $this->extension);
    }

    public function testGetBundleReturnsCorrectStructure(): void
    {
        $bundle = $this->extension->getBundle();

        $this->assertNotNull($bundle);
        $this->assertArrayHasKey('dir', $bundle);
        $this->assertArrayHasKey('namespace', $bundle);
        $this->assertArrayHasKey('exclude', $bundle);
    }

    public function testGetBundleNamespaceIsCorrect(): void
    {
        $bundle = $this->extension->getBundle();

        $this->assertSame('BackTo\\Framework\\OptionsResolver\\', $bundle['namespace']);
    }

    public function testGetBundleExcludesTestsAndContracts(): void
    {
        $bundle = $this->extension->getBundle();

        $this->assertStringContainsString('Tests', $bundle['exclude']);
        $this->assertStringContainsString('Contracts', $bundle['exclude']);
    }

    public function testRegisterBindsOptionsResolverInterface(): void
    {
        $this->extension->register($this->container);

        $this->assertTrue($this->container->has(OptionsResolverInterface::class));
    }

    public function testRegisterAliasesOptionsResolver(): void
    {
        $this->extension->register($this->container);

        $this->assertTrue($this->container->has(OptionsResolver::class));
    }

    public function testRegisteredServiceIsNotShared(): void
    {
        $this->extension->register($this->container);

        $definition = $this->container->getDefinition(OptionsResolverInterface::class);
        $this->assertFalse($definition->isShared());
    }

    public function testGetDefaultConfigurationReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->extension->getDefaultConfiguration());
    }

    public function testRegisterCanBeCalledMultipleTimes(): void
    {
        $this->extension->register($this->container);
        $this->extension->register($this->container);

        $this->assertTrue($this->container->has(OptionsResolverInterface::class));
    }
}
