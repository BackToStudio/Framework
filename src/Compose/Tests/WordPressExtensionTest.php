<?php

declare(strict_types=1);

namespace BackTo\Framework\Compose\Tests;

use BackTo\Framework\Blocks\DependencyInjection\Compiler\RegisterBlockPass;
use BackTo\Framework\Blocks\DependencyInjection\Compiler\RegisterBlockStylePass;
use BackTo\Framework\Compose\DependencyInjection\Compiler\ResolveInstanceOfConditionalPassWithVendorPrefix;
use BackTo\Framework\Compose\DependencyInjection\WordPressExtension;
use BackTo\Framework\Contracts\BlockInterface;
use BackTo\Framework\Contracts\BlockStyleInterface;
use BackTo\Framework\Contracts\HookInterface;
use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\Hooks\DependencyInjection\Compiler\RegisterHookPass;
use BackTo\Framework\PostMeta\Contracts\PostMetaStructureInterface;
use BackTo\Framework\PostMeta\DependencyInjection\Compiler\RegisterPostMetaStructurePass;
use BackTo\Framework\PostType\Contracts\PostTypeInterface;
use BackTo\Framework\PostType\DependencyInjection\Compiler\RegisterPostTypePass;
use BackTo\Framework\Taxonomy\Contracts\TaxonomyInterface;
use BackTo\Framework\Taxonomy\DependencyInjection\Compiler\RegisterTaxonomyPass;
use BackToVendor\Symfony\Component\DependencyInjection\Compiler\ResolveInstanceofConditionalsPass;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class WordPressExtensionTest extends TestCase
{
    private WordPressExtension $extension;
    private ContainerBuilder $containerBuilder;

    protected function setUp(): void
    {
        $this->extension = new WordPressExtension();
        $this->containerBuilder = new ContainerBuilder();
    }

    public function testRegistersAutoconfigurationForRegistryInterface(): void
    {
        $this->extension->registerAutoconfiguration($this->containerBuilder);

        $autoconfigured = $this->containerBuilder->getAutoconfiguredInstanceof();
        $this->assertArrayHasKey(RegistryInterface::class, $autoconfigured);
        $this->assertTrue($autoconfigured[RegistryInterface::class]->isPublic());
    }

    /**
     * @dataProvider autoconfigurationTagsProvider
     */
    public function testRegistersAutoconfigurationTags(string $interface, string $expectedTag): void
    {
        $this->extension->registerAutoconfiguration($this->containerBuilder);

        $autoconfigured = $this->containerBuilder->getAutoconfiguredInstanceof();
        $this->assertArrayHasKey($interface, $autoconfigured);

        $tags = $autoconfigured[$interface]->getTags();
        $this->assertArrayHasKey($expectedTag, $tags);
    }

    public function autoconfigurationTagsProvider(): array
    {
        return [
            'PostType' => [PostTypeInterface::class, 'wordpress.post_type'],
            'PostMeta' => [PostMetaStructureInterface::class, 'wordpress.post_meta'],
            'Taxonomy' => [TaxonomyInterface::class, 'wordpress.taxonomy'],
            'Block' => [BlockInterface::class, 'wordpress.block'],
            'BlockStyle' => [BlockStyleInterface::class, 'wordpress.block_style'],
            'Hook' => [HookInterface::class, 'wordpress.hook'],
        ];
    }

    public function testRegistersAllCompilerPasses(): void
    {
        $this->extension->registerCompilerPasses($this->containerBuilder);

        $passes = $this->containerBuilder->getCompilerPassConfig()->getBeforeOptimizationPasses();
        $passClasses = array_map('get_class', $passes);

        $this->assertContains(RegisterPostTypePass::class, $passClasses);
        $this->assertContains(RegisterPostMetaStructurePass::class, $passClasses);
        $this->assertContains(RegisterTaxonomyPass::class, $passClasses);
        $this->assertContains(RegisterBlockPass::class, $passClasses);
        $this->assertContains(RegisterBlockStylePass::class, $passClasses);
        $this->assertContains(RegisterHookPass::class, $passClasses);
    }

    public function testReplacesResolveInstanceofConditionalsPass(): void
    {
        $this->extension->registerCompilerPasses($this->containerBuilder);

        $passes = $this->containerBuilder->getCompilerPassConfig()->getBeforeOptimizationPasses();
        $passClasses = array_map('get_class', $passes);

        $this->assertNotContains(ResolveInstanceofConditionalsPass::class, $passClasses);
        $this->assertContains(ResolveInstanceOfConditionalPassWithVendorPrefix::class, $passClasses);
    }

    public function testConfigureAppliesBothAutoconfigurationAndCompilerPasses(): void
    {
        $this->extension->configure($this->containerBuilder);

        // Autoconfiguration registered
        $autoconfigured = $this->containerBuilder->getAutoconfiguredInstanceof();
        $this->assertCount(7, $autoconfigured);

        // Compiler passes registered
        $passes = $this->containerBuilder->getCompilerPassConfig()->getBeforeOptimizationPasses();
        $passClasses = array_map('get_class', $passes);
        $this->assertContains(RegisterHookPass::class, $passClasses);
        $this->assertContains(RegisterPostTypePass::class, $passClasses);
    }
}
