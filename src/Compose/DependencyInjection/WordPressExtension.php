<?php

declare(strict_types=1);

namespace BackTo\Framework\Compose\DependencyInjection;

use BackTo\Framework\Admin\Contracts\AdminPageInterface;
use BackTo\Framework\Admin\Contracts\AdminPageRegistrarInterface;
use BackTo\Framework\Admin\DependencyInjection\Compiler\RegisterAdminPagePass;
use BackTo\Framework\Admin\Infrastructure\WordPressAdminPageRegistrar;
use BackTo\Framework\Assets\Contracts\FileLocatorInterface;
use BackTo\Framework\Assets\Infrastructure\WordPressFileLocator;
use BackTo\Framework\Blocks\Contracts\BlockStyleRegistrarInterface;
use BackTo\Framework\Blocks\DependencyInjection\Compiler\RegisterBlockPass;
use BackTo\Framework\Blocks\DependencyInjection\Compiler\RegisterBlockStylePass;
use BackTo\Framework\Blocks\Infrastructure\WordPressBlockStyleRegistrar;
use BackTo\Framework\Compose\DependencyInjection\Compiler\ResolveInstanceOfConditionalPassWithVendorPrefix;
use BackTo\Framework\Contracts\BlockInterface;
use BackTo\Framework\Contracts\BlockStyleInterface;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\HookInterface;
use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\Hooks\DependencyInjection\Compiler\RegisterHookPass;
use BackTo\Framework\Hooks\Infrastructure\WordPressHookDispatcher;
use BackTo\Framework\Options\Contracts\OptionsRepositoryInterface;
use BackTo\Framework\Options\Infrastructure\WordPressOptionsRepository;
use BackTo\Framework\PostMeta\Contracts\PostMetaRegistrarInterface;
use BackTo\Framework\PostMeta\Contracts\PostMetaStructureInterface;
use BackTo\Framework\PostMeta\DependencyInjection\Compiler\RegisterPostMetaStructurePass;
use BackTo\Framework\PostMeta\Infrastructure\WordPressPostMetaRegistrar;
use BackTo\Framework\PostType\Contracts\PostTypeInterface;
use BackTo\Framework\PostType\Contracts\PostTypeRegistrarInterface;
use BackTo\Framework\PostType\DependencyInjection\Compiler\RegisterPostTypePass;
use BackTo\Framework\PostType\Infrastructure\WordPressPostTypeRegistrar;
use BackTo\Framework\RestApi\Contracts\RestRouteInterface;
use BackTo\Framework\RestApi\Contracts\RestRouteRegistrarInterface;
use BackTo\Framework\RestApi\DependencyInjection\Compiler\RegisterRestRoutePass;
use BackTo\Framework\RestApi\Infrastructure\WordPressRestRouteRegistrar;
use BackTo\Framework\Taxonomy\Contracts\TaxonomyInterface;
use BackTo\Framework\Taxonomy\Contracts\TaxonomyRegistrarInterface;
use BackTo\Framework\Taxonomy\DependencyInjection\Compiler\RegisterTaxonomyPass;
use BackTo\Framework\Taxonomy\Infrastructure\WordPressTaxonomyRegistrar;
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

        $containerBuilder->registerForAutoconfiguration(AdminPageInterface::class)
            ->addTag('wordpress.admin_page');

        $containerBuilder->registerForAutoconfiguration(RestRouteInterface::class)
            ->addTag('wordpress.rest_route');
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
        $containerBuilder->addCompilerPass(new RegisterAdminPagePass());
        $containerBuilder->addCompilerPass(new RegisterRestRoutePass());
    }

    /**
     * Register port → adapter bindings (Clean Architecture).
     *
     * Maps domain port interfaces to their WordPress infrastructure adapters.
     */
    public function registerPortBindings(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(HookDispatcherInterface::class, WordPressHookDispatcher::class);
        $containerBuilder->setAlias(WordPressHookDispatcher::class, HookDispatcherInterface::class);

        $containerBuilder->register(PostTypeRegistrarInterface::class, WordPressPostTypeRegistrar::class);
        $containerBuilder->setAlias(WordPressPostTypeRegistrar::class, PostTypeRegistrarInterface::class);

        $containerBuilder->register(TaxonomyRegistrarInterface::class, WordPressTaxonomyRegistrar::class);
        $containerBuilder->setAlias(WordPressTaxonomyRegistrar::class, TaxonomyRegistrarInterface::class);

        $containerBuilder->register(BlockStyleRegistrarInterface::class, WordPressBlockStyleRegistrar::class);
        $containerBuilder->setAlias(WordPressBlockStyleRegistrar::class, BlockStyleRegistrarInterface::class);

        $containerBuilder->register(PostMetaRegistrarInterface::class, WordPressPostMetaRegistrar::class);
        $containerBuilder->setAlias(WordPressPostMetaRegistrar::class, PostMetaRegistrarInterface::class);

        $containerBuilder->register(FileLocatorInterface::class, WordPressFileLocator::class);
        $containerBuilder->setAlias(WordPressFileLocator::class, FileLocatorInterface::class);

        $containerBuilder->register(OptionsRepositoryInterface::class, WordPressOptionsRepository::class);
        $containerBuilder->setAlias(WordPressOptionsRepository::class, OptionsRepositoryInterface::class);

        $containerBuilder->register(AdminPageRegistrarInterface::class, WordPressAdminPageRegistrar::class);
        $containerBuilder->setAlias(WordPressAdminPageRegistrar::class, AdminPageRegistrarInterface::class);

        $containerBuilder->register(RestRouteRegistrarInterface::class, WordPressRestRouteRegistrar::class);
        $containerBuilder->setAlias(WordPressRestRouteRegistrar::class, RestRouteRegistrarInterface::class);
    }

    /**
     * Apply all WordPress DI configuration to the container.
     */
    public function configure(ContainerBuilder $containerBuilder): void
    {
        $this->registerPortBindings($containerBuilder);
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
