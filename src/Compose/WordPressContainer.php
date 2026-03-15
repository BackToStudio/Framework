<?php

declare(strict_types=1);

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

            /** @var HookRegistry $hooksRegistry */
            $hooksRegistry = $container->get(HookRegistry::class);
            $hooksRegistry->runHooks();
        } catch (Exception $e) {
            if ($this->isDebug()) {
                throw $e;
            }
            \error_log(sprintf('[BackTo Framework] %s', $e->getMessage()));
        }
    }

    protected function configureWordPressContainer(ContainerBuilder $containerBuilder): ContainerBuilder
    {
        $extension = new WordPressExtension();
        $extension->configure($containerBuilder);

        return $containerBuilder;
    }

    /**
     * Return the service bundle directories to load.
     *
     * @return array<array{dir: string, namespace: string, exclude: string}>
     */
    protected function getBundles(): array
    {
        return [
            [
                'dir' => dirname(__DIR__) . '/Assets',
                'namespace' => 'BackTo\\Framework\\Assets\\',
                'exclude' => '{DependencyInjection,Entity,Tests,Contracts,Infrastructure}',
            ],
            [
                'dir' => dirname(__DIR__) . '/Hooks',
                'namespace' => 'BackTo\\Framework\\Hooks\\',
                'exclude' => '{DependencyInjection,Entity,Tests,Contracts,Infrastructure}',
            ],
            [
                'dir' => dirname(__DIR__) . '/Blocks',
                'namespace' => 'BackTo\\Framework\\Blocks\\',
                'exclude' => '{DependencyInjection,Entity,Tests,Contracts,Infrastructure}',
            ],
            [
                'dir' => dirname(__DIR__) . '/PostType',
                'namespace' => 'BackTo\\Framework\\PostType\\',
                'exclude' => '{DependencyInjection,Entity,Tests,Contracts,Infrastructure}',
            ],
            [
                'dir' => dirname(__DIR__) . '/Taxonomy',
                'namespace' => 'BackTo\\Framework\\Taxonomy\\',
                'exclude' => '{DependencyInjection,Entity,Tests,Contracts,Infrastructure}',
            ],
            [
                'dir' => dirname(__DIR__) . '/PostMeta',
                'namespace' => 'BackTo\\Framework\\PostMeta\\',
                'exclude' => '{DependencyInjection,Entity,Tests,Contracts,Infrastructure}',
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
            [
                'dir' => dirname(__DIR__) . '/Admin',
                'namespace' => 'BackTo\\Framework\\Admin\\',
                'exclude' => '{DependencyInjection,Tests,Contracts,Infrastructure}',
            ],
            [
                'dir' => dirname(__DIR__) . '/Options',
                'namespace' => 'BackTo\\Framework\\Options\\',
                'exclude' => '{Tests,Contracts,Infrastructure}',
            ],
            [
                'dir' => dirname(__DIR__) . '/RestApi',
                'namespace' => 'BackTo\\Framework\\RestApi\\',
                'exclude' => '{DependencyInjection,Tests,Contracts,Infrastructure}',
            ],
            [
                'dir' => dirname(__DIR__) . '/Observability',
                'namespace' => 'BackTo\\Framework\\Observability\\',
                'exclude' => '{DependencyInjection,Tests,Contracts,Infrastructure,HealthCheck}',
            ],
        ];
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
}
