<?php

declare(strict_types=1);

namespace BackTo\Framework\Compose\DependencyInjection;

use BackTo\Framework\Admin\Contracts\AdminPageInterface;
use BackTo\Framework\Admin\Contracts\AdminPageRegistrarInterface;
use BackTo\Framework\Observability\Contracts\ErrorHandlerInterface;
use BackTo\Framework\Performance\Contracts\DatabaseOptimizerInterface;
use BackTo\Framework\Performance\Contracts\HtmlOptimizerInterface;
use BackTo\Framework\Performance\Contracts\PageCacheInterface;
use BackTo\Framework\Performance\Infrastructure\WordPressDatabaseOptimizer;
use BackTo\Framework\Performance\Infrastructure\WordPressHtmlOptimizer;
use BackTo\Framework\Performance\Infrastructure\WordPressPageCache;
use BackTo\Framework\Observability\Contracts\HealthCheckInterface;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Observability\Contracts\PerformanceCollectorInterface;
use BackTo\Framework\Observability\DependencyInjection\Compiler\RegisterHealthCheckPass;
use BackTo\Framework\Security\Contracts\AuditLogRepositoryInterface;
use BackTo\Framework\Security\Contracts\ContentSecurityPolicyInterface;
use BackTo\Framework\Security\Contracts\CorsManagerInterface;
use BackTo\Framework\Security\Contracts\FileIntegrityRepositoryInterface;
use BackTo\Framework\Security\Contracts\InputSanitizerInterface;
use BackTo\Framework\Security\Contracts\IPAccessControlInterface;
use BackTo\Framework\Security\Contracts\LoginLocationRepositoryInterface;
use BackTo\Framework\Security\Contracts\LoginThrottleInterface;
use BackTo\Framework\Security\Contracts\NonceManagerInterface;
use BackTo\Framework\Security\Contracts\OutputEscaperInterface;
use BackTo\Framework\Security\Contracts\RateLimiterRepositoryInterface;
use BackTo\Framework\Security\Contracts\SecurityNotifierInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Security\Contracts\SubresourceIntegrityInterface;
use BackTo\Framework\Security\ContentSecurityPolicyManager;
use BackTo\Framework\Security\CorsManager;
use BackTo\Framework\Security\DependencyInjection\Compiler\RegisterSecurityRulePass;
use BackTo\Framework\Security\IPAccessControl;
use BackTo\Framework\Security\SecurityNotifier;
use BackTo\Framework\Security\SubresourceIntegrity;
use BackTo\Framework\Security\Infrastructure\WordPressAuditLogRepository;
use BackTo\Framework\Security\Infrastructure\WordPressFileIntegrityRepository;
use BackTo\Framework\Security\Infrastructure\WordPressInputSanitizer;
use BackTo\Framework\Security\Infrastructure\WordPressRateLimiterRepository;
use BackTo\Framework\Security\Infrastructure\WordPressLoginLocationRepository;
use BackTo\Framework\Security\Infrastructure\WordPressLoginThrottle;
use BackTo\Framework\Security\Infrastructure\WordPressNonceManager;
use BackTo\Framework\Security\Infrastructure\WordPressOutputEscaper;
use BackTo\Framework\Security\TwoFactor\BackupCodeManager;
use BackTo\Framework\Security\TwoFactor\Contracts\BackupCodeManagerInterface;
use BackTo\Framework\Security\TwoFactor\Contracts\TotpProviderInterface;
use BackTo\Framework\Security\TwoFactor\Contracts\TwoFactorRepositoryInterface;
use BackTo\Framework\Security\TwoFactor\Infrastructure\WordPressTwoFactorRepository;
use BackTo\Framework\Security\TwoFactor\TotpProvider;
use BackTo\Framework\Observability\ErrorHandler;
use BackTo\Framework\Observability\Infrastructure\NullLogger;
use BackTo\Framework\Observability\Infrastructure\WordPressLogger;
use BackTo\Framework\Observability\PerformanceCollector;
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

        $containerBuilder->registerForAutoconfiguration(HealthCheckInterface::class)
            ->addTag('wordpress.health_check');

        $containerBuilder->registerForAutoconfiguration(SecurityRuleInterface::class)
            ->addTag('wordpress.security_rule');
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
        $containerBuilder->addCompilerPass(new RegisterHealthCheckPass());
        $containerBuilder->addCompilerPass(new RegisterSecurityRulePass());
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

        $containerBuilder->register(LoggerInterface::class, WordPressLogger::class);
        $containerBuilder->setAlias(WordPressLogger::class, LoggerInterface::class);

        $containerBuilder->register(ErrorHandlerInterface::class, ErrorHandler::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(ErrorHandler::class, ErrorHandlerInterface::class);

        $containerBuilder->register(PerformanceCollectorInterface::class, PerformanceCollector::class);
        $containerBuilder->setAlias(PerformanceCollector::class, PerformanceCollectorInterface::class);

        $containerBuilder->register(NonceManagerInterface::class, WordPressNonceManager::class);
        $containerBuilder->setAlias(WordPressNonceManager::class, NonceManagerInterface::class);

        $containerBuilder->register(InputSanitizerInterface::class, WordPressInputSanitizer::class);
        $containerBuilder->setAlias(WordPressInputSanitizer::class, InputSanitizerInterface::class);

        $containerBuilder->register(LoginThrottleInterface::class, WordPressLoginThrottle::class);
        $containerBuilder->setAlias(WordPressLoginThrottle::class, LoginThrottleInterface::class);

        $containerBuilder->register(ContentSecurityPolicyInterface::class, ContentSecurityPolicyManager::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(ContentSecurityPolicyManager::class, ContentSecurityPolicyInterface::class);

        $containerBuilder->register(OutputEscaperInterface::class, WordPressOutputEscaper::class);
        $containerBuilder->setAlias(WordPressOutputEscaper::class, OutputEscaperInterface::class);

        $containerBuilder->register(TotpProviderInterface::class, TotpProvider::class);
        $containerBuilder->setAlias(TotpProvider::class, TotpProviderInterface::class);

        $containerBuilder->register(TwoFactorRepositoryInterface::class, WordPressTwoFactorRepository::class);
        $containerBuilder->setAlias(WordPressTwoFactorRepository::class, TwoFactorRepositoryInterface::class);

        $containerBuilder->register(BackupCodeManagerInterface::class, BackupCodeManager::class);
        $containerBuilder->setAlias(BackupCodeManager::class, BackupCodeManagerInterface::class);

        $containerBuilder->register(AuditLogRepositoryInterface::class, WordPressAuditLogRepository::class);
        $containerBuilder->setAlias(WordPressAuditLogRepository::class, AuditLogRepositoryInterface::class);

        $containerBuilder->register(FileIntegrityRepositoryInterface::class, WordPressFileIntegrityRepository::class);
        $containerBuilder->setAlias(WordPressFileIntegrityRepository::class, FileIntegrityRepositoryInterface::class);

        $containerBuilder->register(LoginLocationRepositoryInterface::class, WordPressLoginLocationRepository::class);
        $containerBuilder->setAlias(WordPressLoginLocationRepository::class, LoginLocationRepositoryInterface::class);

        $containerBuilder->register(CorsManagerInterface::class, CorsManager::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(CorsManager::class, CorsManagerInterface::class);

        $containerBuilder->register(SubresourceIntegrityInterface::class, SubresourceIntegrity::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(SubresourceIntegrity::class, SubresourceIntegrityInterface::class);

        $containerBuilder->register(IPAccessControlInterface::class, IPAccessControl::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(IPAccessControl::class, IPAccessControlInterface::class);

        $containerBuilder->register(RateLimiterRepositoryInterface::class, WordPressRateLimiterRepository::class);
        $containerBuilder->setAlias(WordPressRateLimiterRepository::class, RateLimiterRepositoryInterface::class);

        $containerBuilder->register(SecurityNotifierInterface::class, SecurityNotifier::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(SecurityNotifier::class, SecurityNotifierInterface::class);

        $containerBuilder->register(HtmlOptimizerInterface::class, WordPressHtmlOptimizer::class);
        $containerBuilder->setAlias(WordPressHtmlOptimizer::class, HtmlOptimizerInterface::class);

        $containerBuilder->register(DatabaseOptimizerInterface::class, WordPressDatabaseOptimizer::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(WordPressDatabaseOptimizer::class, DatabaseOptimizerInterface::class);

        $containerBuilder->register(PageCacheInterface::class, WordPressPageCache::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(WordPressPageCache::class, PageCacheInterface::class);
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
