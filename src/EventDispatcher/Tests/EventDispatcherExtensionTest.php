<?php

declare(strict_types=1);

namespace BackTo\Framework\EventDispatcher\Tests;

use BackTo\Framework\Contracts\ExtensionInterface;
use BackTo\Framework\EventDispatcher\Contracts\EventDispatcherInterface;
use BackTo\Framework\EventDispatcher\Contracts\EventSubscriberInterface;
use BackTo\Framework\EventDispatcher\DependencyInjection\Compiler\RegisterEventSubscriberPass;
use BackTo\Framework\EventDispatcher\EventDispatcher;
use BackTo\Framework\EventDispatcher\EventDispatcherExtension;
use BackTo\Framework\EventDispatcher\Infrastructure\WordPressEventBridge;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BackTo\Framework\EventDispatcher\EventDispatcherExtension
 */
class EventDispatcherExtensionTest extends TestCase
{
    private EventDispatcherExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new EventDispatcherExtension();
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

        $this->assertSame('BackTo\\Framework\\EventDispatcher\\', $bundle['namespace']);
    }

    public function testGetBundleExcludesInfrastructureAndTests(): void
    {
        $bundle = $this->extension->getBundle();

        $this->assertStringContainsString('Infrastructure', $bundle['exclude']);
        $this->assertStringContainsString('Tests', $bundle['exclude']);
        $this->assertStringContainsString('Contracts', $bundle['exclude']);
        $this->assertStringContainsString('DependencyInjection', $bundle['exclude']);
    }

    public function testRegisterSetsUpAutoconfigurationForEventSubscriberInterface(): void
    {
        $this->extension->register($this->container);

        $autoconfigured = $this->container->getAutoconfiguredInstanceof();
        $this->assertArrayHasKey(EventSubscriberInterface::class, $autoconfigured);

        $tags = $autoconfigured[EventSubscriberInterface::class]->getTags();
        $this->assertArrayHasKey('backto.event_subscriber', $tags);
    }

    public function testRegisterAddsCompilerPass(): void
    {
        $this->extension->register($this->container);

        $passes = $this->container->getCompilerPassConfig()->getBeforeOptimizationPasses();
        $passClasses = array_map('get_class', $passes);

        $this->assertContains(RegisterEventSubscriberPass::class, $passClasses);
    }

    public function testRegisterBindsEventDispatcher(): void
    {
        $this->extension->register($this->container);

        $this->assertTrue($this->container->has(EventDispatcher::class));
    }

    public function testRegisterBindsWordPressEventBridge(): void
    {
        $this->extension->register($this->container);

        $this->assertTrue($this->container->has(WordPressEventBridge::class));
    }

    public function testRegisterAliasesEventDispatcherInterface(): void
    {
        $this->extension->register($this->container);

        $this->assertTrue($this->container->has(EventDispatcherInterface::class));
    }

    public function testGetDefaultConfigurationReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->extension->getDefaultConfiguration());
    }

    public function testRegisterCanBeCalledMultipleTimes(): void
    {
        $this->extension->register($this->container);
        $this->extension->register($this->container);

        $this->assertTrue($this->container->has(EventDispatcherInterface::class));
    }
}
