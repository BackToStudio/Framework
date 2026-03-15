<?php

declare(strict_types=1);

namespace BackTo\Framework\Compose\Tests;

use BackTo\Framework\Admin\AdminExtension;
use BackTo\Framework\Admin\Contracts\AdminPageInterface;
use BackTo\Framework\Admin\DependencyInjection\Compiler\RegisterAdminPagePass;
use BackTo\Framework\Assets\AssetsExtension;
use BackTo\Framework\Blocks\BlocksExtension;
use BackTo\Framework\Blocks\DependencyInjection\Compiler\RegisterBlockPass;
use BackTo\Framework\Blocks\DependencyInjection\Compiler\RegisterBlockStylePass;
use BackTo\Framework\Cache\CacheExtension;
use BackTo\Framework\Contracts\BlockInterface;
use BackTo\Framework\Contracts\BlockStyleInterface;
use BackTo\Framework\Contracts\ExtensionInterface;
use BackTo\Framework\Contracts\HookInterface;
use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\Hooks\HooksExtension;
use BackTo\Framework\Hooks\DependencyInjection\Compiler\RegisterHookPass;
use BackTo\Framework\Observability\Contracts\HealthCheckInterface;
use BackTo\Framework\Observability\DependencyInjection\Compiler\RegisterHealthCheckPass;
use BackTo\Framework\Observability\ObservabilityExtension;
use BackTo\Framework\Options\OptionsExtension;
use BackTo\Framework\PostMeta\Contracts\PostMetaStructureInterface;
use BackTo\Framework\PostMeta\DependencyInjection\Compiler\RegisterPostMetaStructurePass;
use BackTo\Framework\PostMeta\PostMetaExtension;
use BackTo\Framework\PostType\Contracts\PostTypeInterface;
use BackTo\Framework\PostType\DependencyInjection\Compiler\RegisterPostTypePass;
use BackTo\Framework\PostType\PostTypeExtension;
use BackTo\Framework\RestApi\Contracts\RestRouteInterface;
use BackTo\Framework\RestApi\DependencyInjection\Compiler\RegisterRestRoutePass;
use BackTo\Framework\RestApi\RestApiExtension;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Security\DependencyInjection\Compiler\RegisterSecurityRulePass;
use BackTo\Framework\Gdpr\Contracts\ConsentCategoryInterface;
use BackTo\Framework\Gdpr\Contracts\TrackingScriptInterface;
use BackTo\Framework\Gdpr\DependencyInjection\Compiler\RegisterConsentCategoryPass;
use BackTo\Framework\Gdpr\DependencyInjection\Compiler\RegisterTrackingScriptPass;
use BackTo\Framework\Gdpr\GdprExtension;
use BackTo\Framework\Security\SecurityExtension;
use BackTo\Framework\Seo\SeoExtension;
use BackTo\Framework\Taxonomy\Contracts\TaxonomyInterface;
use BackTo\Framework\Taxonomy\DependencyInjection\Compiler\RegisterTaxonomyPass;
use BackTo\Framework\Taxonomy\TaxonomyExtension;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use PHPUnit\Framework\TestCase;

class WordPressExtensionTest extends TestCase
{
    private ContainerBuilder $containerBuilder;

    protected function setUp(): void
    {
        $this->containerBuilder = new ContainerBuilder();
    }

    /**
     * @dataProvider extensionImplementsInterfaceProvider
     */
    public function testExtensionImplementsInterface(ExtensionInterface $extension): void
    {
        $this->assertInstanceOf(ExtensionInterface::class, $extension);
    }

    public function extensionImplementsInterfaceProvider(): array
    {
        return [
            'Hooks' => [new HooksExtension()],
            'PostType' => [new PostTypeExtension()],
            'Taxonomy' => [new TaxonomyExtension()],
            'Blocks' => [new BlocksExtension()],
            'PostMeta' => [new PostMetaExtension()],
            'Admin' => [new AdminExtension()],
            'RestApi' => [new RestApiExtension()],
            'Observability' => [new ObservabilityExtension()],
            'Security' => [new SecurityExtension()],
            'Assets' => [new AssetsExtension()],
            'Options' => [new OptionsExtension()],
            'Cache' => [new CacheExtension()],
            'Seo' => [new SeoExtension()],
            'Gdpr' => [new GdprExtension()],
        ];
    }

    /**
     * @dataProvider autoconfigurationTagsProvider
     */
    public function testExtensionRegistersAutoconfigurationTags(
        ExtensionInterface $extension,
        string $interface,
        string $expectedTag
    ): void {
        $extension->register($this->containerBuilder);

        $autoconfigured = $this->containerBuilder->getAutoconfiguredInstanceof();
        $this->assertArrayHasKey($interface, $autoconfigured);

        $tags = $autoconfigured[$interface]->getTags();
        $this->assertArrayHasKey($expectedTag, $tags);
    }

    public function autoconfigurationTagsProvider(): array
    {
        return [
            'PostType' => [new PostTypeExtension(), PostTypeInterface::class, 'wordpress.post_type'],
            'PostMeta' => [new PostMetaExtension(), PostMetaStructureInterface::class, 'wordpress.post_meta'],
            'Taxonomy' => [new TaxonomyExtension(), TaxonomyInterface::class, 'wordpress.taxonomy'],
            'Block' => [new BlocksExtension(), BlockInterface::class, 'wordpress.block'],
            'BlockStyle' => [new BlocksExtension(), BlockStyleInterface::class, 'wordpress.block_style'],
            'Hook' => [new HooksExtension(), HookInterface::class, 'wordpress.hook'],
            'AdminPage' => [new AdminExtension(), AdminPageInterface::class, 'wordpress.admin_page'],
            'RestRoute' => [new RestApiExtension(), RestRouteInterface::class, 'wordpress.rest_route'],
            'HealthCheck' => [new ObservabilityExtension(), HealthCheckInterface::class, 'wordpress.health_check'],
            'SecurityRule' => [new SecurityExtension(), SecurityRuleInterface::class, 'wordpress.security_rule'],
            'ConsentCategory' => [new GdprExtension(), ConsentCategoryInterface::class, 'wordpress.consent_category'],
            'TrackingScript' => [new GdprExtension(), TrackingScriptInterface::class, 'wordpress.tracking_script'],
        ];
    }

    /**
     * @dataProvider compilerPassProvider
     */
    public function testExtensionRegistersCompilerPass(ExtensionInterface $extension, string $expectedPassClass): void
    {
        $extension->register($this->containerBuilder);

        $passes = $this->containerBuilder->getCompilerPassConfig()->getBeforeOptimizationPasses();
        $passClasses = array_map('get_class', $passes);

        $this->assertContains($expectedPassClass, $passClasses);
    }

    public function compilerPassProvider(): array
    {
        return [
            'PostType' => [new PostTypeExtension(), RegisterPostTypePass::class],
            'PostMeta' => [new PostMetaExtension(), RegisterPostMetaStructurePass::class],
            'Taxonomy' => [new TaxonomyExtension(), RegisterTaxonomyPass::class],
            'Block' => [new BlocksExtension(), RegisterBlockPass::class],
            'BlockStyle' => [new BlocksExtension(), RegisterBlockStylePass::class],
            'Hook' => [new HooksExtension(), RegisterHookPass::class],
            'AdminPage' => [new AdminExtension(), RegisterAdminPagePass::class],
            'RestRoute' => [new RestApiExtension(), RegisterRestRoutePass::class],
            'HealthCheck' => [new ObservabilityExtension(), RegisterHealthCheckPass::class],
            'SecurityRule' => [new SecurityExtension(), RegisterSecurityRulePass::class],
            'ConsentCategory' => [new GdprExtension(), RegisterConsentCategoryPass::class],
            'TrackingScript' => [new GdprExtension(), RegisterTrackingScriptPass::class],
        ];
    }

    public function testExtensionReturnsBundleConfiguration(): void
    {
        $extension = new PostTypeExtension();
        $bundle = $extension->getBundle();

        $this->assertNotNull($bundle);
        $this->assertArrayHasKey('dir', $bundle);
        $this->assertArrayHasKey('namespace', $bundle);
        $this->assertArrayHasKey('exclude', $bundle);
    }

    public function testExtensionReturnsDefaultConfiguration(): void
    {
        $extension = new SecurityExtension();
        $defaults = $extension->getDefaultConfiguration();

        $this->assertArrayHasKey('framework.security.headers_enabled', $defaults);
        $this->assertArrayHasKey('framework.security.two_factor_enabled', $defaults);
    }

    public function testSecurityExtensionHasMultipleBundles(): void
    {
        $extension = new SecurityExtension();

        $this->assertNull($extension->getBundle());
        $this->assertCount(2, $extension->getBundles());
    }

    public function testAllExtensionsRegisterWithoutConflict(): void
    {
        $extensions = [
            new HooksExtension(),
            new PostTypeExtension(),
            new TaxonomyExtension(),
            new BlocksExtension(),
            new PostMetaExtension(),
            new AdminExtension(),
            new RestApiExtension(),
            new ObservabilityExtension(),
            new SecurityExtension(),
            new AssetsExtension(),
            new OptionsExtension(),
            new CacheExtension(),
            new SeoExtension(),
            new GdprExtension(),
        ];

        foreach ($extensions as $extension) {
            foreach ($extension->getDefaultConfiguration() as $key => $value) {
                if (!$this->containerBuilder->hasParameter($key)) {
                    $this->containerBuilder->setParameter($key, $value);
                }
            }
            $extension->register($this->containerBuilder);
        }

        $autoconfigured = $this->containerBuilder->getAutoconfiguredInstanceof();
        $this->assertNotEmpty($autoconfigured);

        $passes = $this->containerBuilder->getCompilerPassConfig()->getBeforeOptimizationPasses();
        $this->assertNotEmpty($passes);
    }
}
