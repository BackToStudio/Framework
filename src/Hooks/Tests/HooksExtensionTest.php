<?php

declare(strict_types=1);

namespace BackTo\Framework\Hooks\Tests;

use BackTo\Framework\Contracts\ExtensionInterface;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\HookInterface;
use BackTo\Framework\Hooks\DependencyInjection\Compiler\RegisterHookPass;
use BackTo\Framework\Hooks\HooksExtension;
use BackTo\Framework\Hooks\Infrastructure\WordPressHookDispatcher;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class HooksExtensionTest extends TestCase
{
    private HooksExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new HooksExtension();
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

        $this->assertSame('BackTo\\Framework\\Hooks\\', $bundle['namespace']);
    }

    public function testGetBundleExcludesInfrastructureAndTests(): void
    {
        $bundle = $this->extension->getBundle();

        $this->assertStringContainsString('Infrastructure', $bundle['exclude']);
        $this->assertStringContainsString('Tests', $bundle['exclude']);
    }

    public function testRegisterSetsUpAutoconfigurationForHookInterface(): void
    {
        $this->extension->register($this->container);

        $autoconfigured = $this->container->getAutoconfiguredInstanceof();
        $this->assertArrayHasKey(HookInterface::class, $autoconfigured);

        $tags = $autoconfigured[HookInterface::class]->getTags();
        $this->assertArrayHasKey('wordpress.hook', $tags);
    }

    public function testRegisterAddsCompilerPass(): void
    {
        $this->extension->register($this->container);

        $passes = $this->container->getCompilerPassConfig()->getBeforeOptimizationPasses();
        $passClasses = array_map('get_class', $passes);

        $this->assertContains(RegisterHookPass::class, $passClasses);
    }

    public function testRegisterBindsHookDispatcherInterface(): void
    {
        $this->extension->register($this->container);

        $this->assertTrue($this->container->has(HookDispatcherInterface::class));
    }

    public function testRegisterAliasesWordPressHookDispatcher(): void
    {
        $this->extension->register($this->container);

        $this->assertTrue($this->container->has(WordPressHookDispatcher::class));
    }

    public function testGetDefaultConfigurationReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->extension->getDefaultConfiguration());
    }

    public function testRegisterCanBeCalledMultipleTimes(): void
    {
        $this->extension->register($this->container);
        $this->extension->register($this->container);

        // Should not throw, should still have the binding
        $this->assertTrue($this->container->has(HookDispatcherInterface::class));
    }
}
