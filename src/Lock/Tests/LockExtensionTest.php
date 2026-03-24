<?php

declare(strict_types=1);

namespace BackTo\Framework\Lock\Tests;

use BackTo\Framework\Contracts\ExtensionInterface;
use BackTo\Framework\Lock\Contracts\LockFactoryInterface;
use BackTo\Framework\Lock\Contracts\LockStoreInterface;
use BackTo\Framework\Lock\Infrastructure\CacheStoreLockStore;
use BackTo\Framework\Lock\LockExtension;
use BackTo\Framework\Lock\LockFactory;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BackTo\Framework\Lock\LockExtension
 */
class LockExtensionTest extends TestCase
{
    private LockExtension $extension;
    private ContainerBuilder $container;

    protected function setUp(): void
    {
        $this->extension = new LockExtension();
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
        $this->assertSame('BackTo\\Framework\\Lock\\', $bundle['namespace']);
    }

    public function testGetBundleExcludesTestsContractsInfrastructure(): void
    {
        $bundle = $this->extension->getBundle();

        $this->assertStringContainsString('Tests', $bundle['exclude']);
        $this->assertStringContainsString('Contracts', $bundle['exclude']);
        $this->assertStringContainsString('Infrastructure', $bundle['exclude']);
    }

    public function testRegisterBindsLockStoreInterface(): void
    {
        $this->extension->register($this->container);

        $this->assertTrue($this->container->has(LockStoreInterface::class));
    }

    public function testRegisterAliasesCacheStoreLockStore(): void
    {
        $this->extension->register($this->container);

        $this->assertTrue($this->container->has(CacheStoreLockStore::class));
    }

    public function testRegisterBindsLockFactoryInterface(): void
    {
        $this->extension->register($this->container);

        $this->assertTrue($this->container->has(LockFactoryInterface::class));
    }

    public function testRegisterAliasesLockFactory(): void
    {
        $this->extension->register($this->container);

        $this->assertTrue($this->container->has(LockFactory::class));
    }

    public function testGetDefaultConfigurationReturnsEmptyArray(): void
    {
        $this->assertSame([], $this->extension->getDefaultConfiguration());
    }

    public function testRegisterCanBeCalledMultipleTimes(): void
    {
        $this->extension->register($this->container);
        $this->extension->register($this->container);

        $this->assertTrue($this->container->has(LockFactoryInterface::class));
    }
}
