<?php

namespace BackTo\Framework\Compose;

use BackTo\Framework\Compose\DependencyInjection\WordPressExtension;
use Exception;
use LogicException;
use ReflectionObject;
use BackTo\Framework\Hooks\HookRegistry;
use BackToVendor\Symfony\Component\Config\Builder\ConfigBuilderGenerator;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerInterface;
use BackToVendor\Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use BackToVendor\Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use BackToVendor\Symfony\Component\Config\ConfigCache;
use BackToVendor\Symfony\Component\Config\ConfigCacheInterface;
use BackToVendor\Symfony\Component\Config\FileLocator;

use function dirname;
use function is_file;
use function is_null;

trait WordPressContainer
{

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var string
     */
    protected $kernelFile;

    /**
     * @var string
     */
    protected $kernelDir;

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

    /**
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * Get Kernel file from root Kernel instanciation.
     *
     * @return string
     */
    public function getKernelFile(): string
    {
        if (is_null($this->kernelFile)) {
            $reflected = new ReflectionObject($this);

            if (!is_file($reflected->getFileName())) {
                throw new LogicException(
                    sprintf('Cannot auto-detect project dir for kernel of class "%s".', $reflected->name)
                );
            }

            $this->kernelFile = $reflected->getFileName();
        }

        return $this->kernelFile;
    }

    /**
     * Get Kernel directory from root Kernel instanciation.
     *
     * @return string
     */
    public function getProjectDir(): string
    {
        if (is_null($this->kernelDir)) {
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

    /**
     * @return string
     */
    public function getBuildDir(): string
    {
        return $this->getProjectDir() . '/var/';
    }

    /**
     * @return ContainerInterface|null
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

    /**
     * @return string
     */
    protected function getContainerHash(): string
    {
        return \str_replace('.', '_', ContainerBuilder::hash($this->getKernelFile()));
    }

    /**
     * @param string $file
     *
     * @return ContainerInterface|null
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

        return new $classname();
    }

    /**
     * Store Container in PHP version.
     *
     * @param ConfigCacheInterface $cache
     * @param ContainerBuilder $container
     */
    protected function dumpContainer(
        ConfigCacheInterface $cache,
        ContainerBuilder $container
    ) {
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

        $cache->write(
            $dumper->dump($options),
            $container->getResources()
        );
    }

    /**
     * Run the Kernel.
     *
     * @throws Exception
     */
    public function load()
    {
        try {
            $container = $this->getContainer();
            /** @var HookRegistry $hooksRegistry */
            $hooksRegistry = $container->get(HookRegistry::class);
            $hooksRegistry->runHooks();
        } catch (Exception $e) {
            if ($this->isDebug()) {
                throw $e;
            }
            // Don't crash the entire site, simply don't load.
        }
    }

    /**
     * Apply WordPress DI configuration (autoconfiguration + compiler passes).
     */
    protected function configureWordPressContainer(ContainerBuilder $containerBuilder): ContainerBuilder
    {
        $extension = new WordPressExtension();
        $extension->configure($containerBuilder);

        return $containerBuilder;
    }

    /**
     * Return the service bundle directories to load.
     *
     * Each entry is a [directory, namespace] pair that will be loaded as services.
     * Override in subclasses to register additional bundles.
     *
     * @return array<array{dir: string, namespace: string, exclude: string}>
     */
    protected function getBundles(): array
    {
        return [
            [
                'dir' => dirname(__DIR__) . '/Assets',
                'namespace' => 'BackTo\\Framework\\Assets\\',
                'exclude' => '{DependencyInjection,Entity,Tests,Contracts}',
            ],
            [
                'dir' => dirname(__DIR__) . '/Hooks',
                'namespace' => 'BackTo\\Framework\\Hooks\\',
                'exclude' => '{DependencyInjection,Entity,Tests,Contracts}',
            ],
            [
                'dir' => dirname(__DIR__) . '/Blocks',
                'namespace' => 'BackTo\\Framework\\Blocks\\',
                'exclude' => '{DependencyInjection,Entity,Tests,Contracts}',
            ],
            [
                'dir' => dirname(__DIR__) . '/PostType',
                'namespace' => 'BackTo\\Framework\\PostType\\',
                'exclude' => '{DependencyInjection,Entity,Tests,Contracts}',
            ],
            [
                'dir' => dirname(__DIR__) . '/Taxonomy',
                'namespace' => 'BackTo\\Framework\\Taxonomy\\',
                'exclude' => '{DependencyInjection,Entity,Tests,Contracts}',
            ],
            [
                'dir' => dirname(__DIR__) . '/PostMeta',
                'namespace' => 'BackTo\\Framework\\PostMeta\\',
                'exclude' => '{DependencyInjection,Entity,Tests,Contracts}',
            ],
            [
                'dir' => dirname(__DIR__) . '/Cache',
                'namespace' => 'BackTo\\Framework\\Cache\\',
                'exclude' => '{Tests,Contracts}',
            ],
            [
                'dir' => dirname(__DIR__) . '/Seo',
                'namespace' => 'BackTo\\Framework\\Seo\\',
                'exclude' => '{Tests,Contracts}',
            ],
        ];
    }

    /**
     * Load framework bundles and the project's own services.
     *
     * @param ContainerBuilder $containerBuilder
     * @throws Exception
     */
    protected function loadServices(ContainerBuilder $containerBuilder): void
    {
        $configBuilderGenerator = ConfigBuilderGenerator::class ? new ConfigBuilderGenerator(
            $this->getBuildDir()
        ) : null;

        // Load framework bundles.
        $this->loadBundles($containerBuilder, $configBuilderGenerator);

        // Load the kernel-specific services (Theme or Plugin I18n, etc.).
        $this->loadKernelServices($containerBuilder, $configBuilderGenerator);

        // Load the project's own services.
        $fileLocator = new FileLocator($this->getProjectDir());
        $loader = new PhpFileLoader($containerBuilder, $fileLocator, $this->getEnvironment(), $configBuilderGenerator);
        $loader->load('config/services.php');
    }

    /**
     * Load all registered framework bundles into the container.
     */
    private function loadBundles(ContainerBuilder $containerBuilder, ?ConfigBuilderGenerator $configBuilderGenerator): void
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

    /**
     * Load kernel-specific services (I18n, etc.).
     * Override in subclasses if the kernel has its own Resources/config/services.php.
     */
    protected function loadKernelServices(ContainerBuilder $containerBuilder, ?ConfigBuilderGenerator $configBuilderGenerator): void
    {
        // Default: no kernel-specific services. Override in AbstractKernel subclasses.
    }
}
