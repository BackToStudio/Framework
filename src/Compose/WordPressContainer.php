<?php

declare(strict_types=1);

namespace BackTo\Framework\Compose;

use BackTo\Framework\Bundle\Admin\AdminExtension;
use BackTo\Framework\Assets\AssetsConfigurator;
use BackTo\Framework\Assets\AssetsExtension;
use BackTo\Framework\Bundle\Blocks\BlocksExtension;
use BackTo\Framework\Cache\CacheConfigurator;
use BackTo\Framework\Cache\CacheExtension;
use BackTo\Framework\Compose\DependencyInjection\Compiler\ResolveInstanceOfConditionalPassWithVendorPrefix;
use BackTo\Framework\Contracts\ExtensionInterface;
use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\EventDispatcher\EventDispatcherExtension;
use BackTo\Framework\Hooks\HooksExtension;
use BackTo\Framework\Http\HttpExtension;
use BackTo\Framework\Lock\LockExtension;
use BackTo\Framework\Hooks\Contracts\HookRegistryInterface;
use BackTo\Framework\Observability\ObservabilityConfigurator;
use BackTo\Framework\OptionsResolver\OptionsResolverExtension;
use BackTo\Framework\Observability\ObservabilityExtension;
use BackTo\Framework\Options\OptionsExtension;
use BackTo\Framework\PostMeta\PostMetaExtension;
use BackTo\Framework\PostType\PostTypeExtension;
use BackTo\Framework\RestApi\RestApiConfigurator;
use BackTo\Framework\RestApi\RestApiExtension;
use BackTo\Framework\Bundle\Gdpr\GdprExtension;
use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;
use BackTo\Framework\Bundle\Performance\PerformanceExtension;
use BackTo\Framework\Contracts\ModuleConfiguratorInterface;
use BackTo\Framework\Bundle\Security\SecurityConfigurator;
use BackTo\Framework\Bundle\Security\SecurityExtension;
use BackTo\Framework\Bundle\Seo\SeoConfigurator;
use BackTo\Framework\Bundle\Seo\SeoExtension;
use BackTo\Framework\Taxonomy\TaxonomyExtension;
use BackTo\Framework\Validation\ValidationExtension;
use BackTo\Framework\WordPress\WordPressExtension;
use Exception;
use LogicException;
use ReflectionObject;
use BackToVendor\Symfony\Component\Config\Builder\ConfigBuilderGenerator;
use BackToVendor\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use BackToVendor\Symfony\Component\DependencyInjection\Compiler\ResolveInstanceofConditionalsPass;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerInterface;
use BackToVendor\Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use BackToVendor\Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use BackToVendor\Symfony\Component\Config\ConfigCache;
use BackToVendor\Symfony\Component\Config\ConfigCacheInterface;
use BackToVendor\Symfony\Component\Config\FileLocator;

use function dirname;
use function is_file;

trait WordPressContainer
{
    protected bool $debug = false;
    protected string $environment = '';
    protected ?string $kernelFile = null;
    protected ?string $kernelDir = null;

    public function isDebug(): bool
    {
        return $this->debug;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Get Kernel file from root Kernel instantiation.
     */
    public function getKernelFile(): string
    {
        if ($this->kernelFile === null) {
            $reflected = new ReflectionObject($this);

            if (!is_file((string) $reflected->getFileName())) {
                throw new LogicException(
                    sprintf('Cannot auto-detect project dir for kernel of class "%s".', $reflected->name)
                );
            }

            $this->kernelFile = (string) $reflected->getFileName();
        }

        return $this->kernelFile;
    }

    /**
     * Get Kernel directory from root Kernel instantiation.
     */
    public function getProjectDir(): string
    {
        if ($this->kernelDir === null) {
            $kernelFile = $this->getKernelFile();
            $dir = $rootDir = dirname($kernelFile);
            while (!is_file($dir . '/composer.json')) {
                if ($dir === dirname($dir)) {
                    return $this->kernelDir = $rootDir;
                }
                $dir = dirname($dir);
            }
            $this->kernelDir = $dir;
        }

        return $this->kernelDir;
    }

    public function getBuildDir(): string
    {
        return $this->getProjectDir() . '/var/';
    }

    /**
     * @throws Exception
     */
    public function getContainer(): ?ContainerInterface
    {
        $file = $this->getBuildDir() . 'container.php';

        return $this->generateContainer($file);
    }

    /**
     * Gets the container's base class.
     *
     * All names except Container must be fully qualified.
     */
    protected function getContainerBaseClass(): string
    {
        return 'Container';
    }

    protected function getContainerHash(): string
    {
        return \str_replace('.', '_', ContainerBuilder::hash($this->getKernelFile()));
    }

    /**
     * @throws Exception
     */
    public function generateContainer(string $file): ?ContainerInterface
    {
        $cache = new ConfigCache($file, $this->isDebug());
        $classname = 'Container' . $this->getContainerHash();

        if (!$cache->isFresh()) {
            $containerBuilder = $this->getContainerBuilder();
            $containerBuilder->compile();
            $this->dumpContainer($cache, $containerBuilder);
        }

        if (!is_file($file)) {
            return null;
        }

        require_once $file;

        /** @var ContainerInterface $container */
        $container = new $classname();

        return $container;
    }

    protected function dumpContainer(
        ConfigCacheInterface $cache,
        ContainerBuilder $container
    ): void {
        $containerBaseClass = $this->getContainerBaseClass();
        $classname = $containerBaseClass . $this->getContainerHash();
        $dumper = new PhpDumper($container);
        $options = [
            'class' => $classname,
            'base_class' => $containerBaseClass,
            'debug' => $this->isDebug(),
            'file' => $cache->getPath(),
            'build_time' => time(),
        ];

        /** @var string $dump */
        $dump = $dumper->dump($options);
        $cache->write(
            $dump,
            $container->getResources()
        );
    }

    /**
     * Run the Kernel.
     *
     * @throws Exception
     */
    public function load(): void
    {
        try {
            $container = $this->getContainer();

            if ($container === null) {
                return;
            }

            /** @var HookRegistryInterface $hooksRegistry */
            $hooksRegistry = $container->get(HookRegistryInterface::class);
            $hooksRegistry->runHooks();
        } catch (Exception $e) {
            if ($this->isDebug()) {
                throw $e;
            }
            \error_log(\sprintf('[BackTo Framework] %s in %s:%d', $e->getMessage(), $e->getFile(), $e->getLine()));
        }
    }

    /**
     * Return the extensions to load into the container.
     *
     * Override this method in your kernel to compose only the modules you need:
     *
     *     protected function getExtensions(): array
     *     {
     *         return [
     *             new HooksExtension(),
     *             new SecurityExtension(),
     *         ];
     *     }
     *
     * @return ExtensionInterface[]
     */
    protected function getExtensions(): array
    {
        return [
            new WordPressExtension(),
            new HttpExtension(),
            new HooksExtension(),
            new AssetsExtension(),
            new BlocksExtension(),
            new PostTypeExtension(),
            new TaxonomyExtension(),
            new PostMetaExtension(),
            new CacheExtension(),
            new LockExtension(),
            new SeoExtension(),
            new AdminExtension(),
            new OptionsExtension(),
            new OptionsResolverExtension(),
            new EventDispatcherExtension(),
            new ValidationExtension(),
            new RestApiExtension(),
            new ObservabilityExtension(),
            new SecurityExtension(),
            new GdprExtension(),
            new PerformanceExtension(),
        ];
    }

    /**
     * Configure the DI container using registered extensions.
     */
    protected function configureWordPressContainer(ContainerBuilder $containerBuilder): ContainerBuilder
    {
        $this->replaceResolveInstanceofConditionalsPass($containerBuilder);

        // Core autoconfiguration (Compose-level concern).
        $containerBuilder->registerForAutoconfiguration(RegistryInterface::class)
            ->setPublic(true);

        // Register each extension.
        foreach ($this->getExtensions() as $extension) {
            try {
                // Apply default configuration parameters.
                foreach ($extension->getDefaultConfiguration() as $key => $value) {
                    if (!$containerBuilder->hasParameter($key)) {
                        $containerBuilder->setParameter($key, $value);
                    }
                }

                // Register compiler passes, autoconfiguration, and port bindings.
                $extension->register($containerBuilder);
            } catch (\Throwable $e) {
                // A single broken extension must not crash the entire application.
                // Log the failure and continue loading remaining extensions.
                \error_log(\sprintf(
                    '[BackTo Framework] Extension %s failed to register: %s in %s:%d',
                    \get_class($extension),
                    $e->getMessage(),
                    $e->getFile(),
                    $e->getLine()
                ));

                if ($this->isDebug()) {
                    throw $e;
                }
            }
        }

        return $containerBuilder;
    }

    /**
     * Return the service bundle directories to load, derived from extensions.
     *
     * @return array<array{dir: string, namespace: string, exclude: string}>
     */
    protected function getBundles(): array
    {
        $bundles = [];

        foreach ($this->getExtensions() as $extension) {
            foreach ($extension->getBundles() as $bundle) {
                $bundles[] = $bundle;
            }
        }

        return $bundles;
    }

    /**
     * @throws Exception
     */
    protected function loadServices(ContainerBuilder $containerBuilder): void
    {
        $configBuilderGenerator = new ConfigBuilderGenerator($this->getBuildDir());

        $this->loadBundles($containerBuilder, $configBuilderGenerator);
        $this->loadKernelServices($containerBuilder, $configBuilderGenerator);

        $fileLocator = new FileLocator($this->getProjectDir());
        $loader = new PhpFileLoader($containerBuilder, $fileLocator, $this->getEnvironment(), $configBuilderGenerator);
        $loader->load('config/services.php');

        $this->loadOptionalConfigFiles($containerBuilder, $configBuilderGenerator);
    }

    /**
     * Load optional per-module configuration files from config/.
     *
     * Each file can either be a standard Symfony config file (ContainerConfigurator)
     * or return a closure that receives a dedicated module configurator.
     *
     * @var array<string, class-string> Maps filename to its configurator class.
     */
    private const MODULE_CONFIGURATORS = [
        'assets.php' => AssetsConfigurator::class,
        'cache.php' => CacheConfigurator::class,
        'observability.php' => ObservabilityConfigurator::class,
        'performance.php' => PerformanceConfigurator::class,
        'rest-api.php' => RestApiConfigurator::class,
        'security.php' => SecurityConfigurator::class,
        'seo.php' => SeoConfigurator::class,
    ];

    /**
     * Load optional per-module configuration files from config/.
     *
     * Looks for config/performance.php, config/security.php alongside config/services.php.
     * This allows overriding module defaults without polluting services.php.
     */
    private function loadOptionalConfigFiles(ContainerBuilder $containerBuilder, ConfigBuilderGenerator $configBuilderGenerator): void
    {
        $configDir = $this->getProjectDir() . '/config';
        $optionalFiles = ['assets.php', 'cache.php', 'observability.php', 'performance.php', 'rest-api.php', 'security.php', 'seo.php'];

        foreach ($optionalFiles as $file) {
            $filePath = $configDir . '/' . $file;
            if (!file_exists($filePath)) {
                continue;
            }

            // Module configurator mode: the file returns a closure that receives a typed configurator.
            if (isset(self::MODULE_CONFIGURATORS[$file])) {
                $this->loadModuleConfigFile($filePath, self::MODULE_CONFIGURATORS[$file], $containerBuilder);
                continue;
            }

            // Standard Symfony ContainerConfigurator mode.
            $fileLocator = new FileLocator($configDir);
            $loader = new PhpFileLoader($containerBuilder, $fileLocator, $this->getEnvironment(), $configBuilderGenerator);
            $loader->load($file);
        }
    }

    /**
     * Load a module config file that returns a closure receiving a typed configurator.
     *
     * @param class-string $configuratorClass
     */
    private function loadModuleConfigFile(string $filePath, string $configuratorClass, ContainerBuilder $containerBuilder): void
    {
        try {
            $callback = require $filePath;

            if (!is_callable($callback)) {
                return;
            }

            /** @var ModuleConfiguratorInterface $configurator */
            $configurator = new $configuratorClass();
            $callback($configurator);

            foreach ($configurator->toParameters() as $key => $value) {
                $containerBuilder->setParameter($key, $value);
            }
        } catch (\Throwable $e) {
            \error_log(\sprintf(
                '[BackTo Framework] Config file %s failed to load: %s',
                basename($filePath),
                $e->getMessage()
            ));

            if ($this->isDebug()) {
                throw $e;
            }
        }
    }

    private function loadBundles(ContainerBuilder $containerBuilder, ConfigBuilderGenerator $configBuilderGenerator): void
    {
        $bundles = $this->getBundles();

        foreach ($bundles as $bundle) {
            $fileLocator = new FileLocator($bundle['dir']);
            $loader = new PhpFileLoader($containerBuilder, $fileLocator, $this->getEnvironment(), $configBuilderGenerator);
            $loader->registerClasses(
                $containerBuilder->register($bundle['namespace'])
                    ->setAutowired(true)
                    ->setAutoconfigured(true),
                $bundle['namespace'],
                $bundle['dir'] . '/*',
                $bundle['dir'] . '/' . $bundle['exclude']
            );
        }
    }

    protected function loadKernelServices(ContainerBuilder $containerBuilder, ConfigBuilderGenerator $configBuilderGenerator): void
    {
        // Default: no kernel-specific services. Override in AbstractKernel subclasses.
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
