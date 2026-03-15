<?php

namespace BackTo\Framework\Compose\DependencyInjection;

use BackTo\Framework\Blocks\DependencyInjection\Compiler\RegisterBlockPass;
use BackTo\Framework\Blocks\DependencyInjection\Compiler\RegisterBlockStylePass;
use BackTo\Framework\Compose\DependencyInjection\Compiler\ResolveInstanceOfConditionalPassWithVendorPrefix;
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
use BackToVendor\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use BackToVendor\Symfony\Component\DependencyInjection\Compiler\ResolveInstanceofConditionalsPass;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

class WordPressExtension
{
    /**
     * Register autoconfiguration rules for WordPress interfaces.
     */
    public function registerAutoconfiguration(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->registerForAutoconfiguration(RegistryInterface::class)
            ->setPublic(true);

        $containerBuilder->registerForAutoconfiguration(PostTypeInterface::class)
            ->addTag('wordpress.post_type');

        $containerBuilder->registerForAutoconfiguration(PostMetaStructureInterface::class)
            ->addTag('wordpress.post_meta');

        $containerBuilder->registerForAutoconfiguration(TaxonomyInterface::class)
            ->addTag('wordpress.taxonomy');

        $containerBuilder->registerForAutoconfiguration(BlockInterface::class)
            ->addTag('wordpress.block');

        $containerBuilder->registerForAutoconfiguration(BlockStyleInterface::class)
            ->addTag('wordpress.block_style');

        $containerBuilder->registerForAutoconfiguration(HookInterface::class)
            ->addTag('wordpress.hook');
    }

    /**
     * Register all compiler passes for WordPress service collection.
     */
    public function registerCompilerPasses(ContainerBuilder $containerBuilder): void
    {
        $this->replaceResolveInstanceofConditionalsPass($containerBuilder);

        $containerBuilder->addCompilerPass(new RegisterPostTypePass());
        $containerBuilder->addCompilerPass(new RegisterPostMetaStructurePass());
        $containerBuilder->addCompilerPass(new RegisterTaxonomyPass());
        $containerBuilder->addCompilerPass(new RegisterBlockPass());
        $containerBuilder->addCompilerPass(new RegisterBlockStylePass());
        $containerBuilder->addCompilerPass(new RegisterHookPass());
    }

    /**
     * Apply all WordPress DI configuration to the container.
     */
    public function configure(ContainerBuilder $containerBuilder): void
    {
        $this->registerAutoconfiguration($containerBuilder);
        $this->registerCompilerPasses($containerBuilder);
    }

    private function replaceResolveInstanceofConditionalsPass(ContainerBuilder $containerBuilder): void
    {
        $beforeOptimizationPasses = $containerBuilder->getCompilerPassConfig()->getBeforeOptimizationPasses();

        $beforeOptimizationPasses = array_filter(
            $beforeOptimizationPasses,
            function (CompilerPassInterface $compilerPass) {
                return (\get_class($compilerPass) !== ResolveInstanceofConditionalsPass::class);
            }
        );

        $containerBuilder->getCompilerPassConfig()->setBeforeOptimizationPasses($beforeOptimizationPasses);
        $containerBuilder->addCompilerPass(new ResolveInstanceOfConditionalPassWithVendorPrefix());
    }
}
