<?php

namespace Fantassin\Core\WordPress\Compose;

use Exception;
use LogicException;
use ReflectionObject;
use Fantassin\Core\WordPress\PostType\Contracts\PostTypeInterface;
use Fantassin\Core\WordPress\Taxonomy\Contracts\TaxonomyInterface;
use Fantassin\Core\WordPress\Blocks\DependencyInjection\Compiler\RegisterBlockPass;
use Fantassin\Core\WordPress\Blocks\DependencyInjection\Compiler\RegisterBlockStylePass;
use Fantassin\Core\WordPress\Compose\DependencyInjection\Compiler\ResolveInstanceOfContiditionalPassWithVendorPrefix;
use Fantassin\Core\WordPress\Contracts\BlockInterface;
use Fantassin\Core\WordPress\Contracts\BlockStyleInterface;
use Fantassin\Core\WordPress\Contracts\HookInterface;
use Fantassin\Core\WordPress\Contracts\RegistryInterface;
use Fantassin\Core\WordPress\Hooks\DependencyInjection\Compiler\RegisterHookPass;
use Fantassin\Core\WordPress\Hooks\HookRegistry;
use Fantassin\Core\WordPress\PostType\DependencyInjection\Compiler\RegisterPostTypePass;
use Fantassin\Core\WordPress\Taxonomy\DependencyInjection\Compiler\RegisterTaxonomyPass;
use FantassinCoreWordPressVendor\Symfony\Component\Config\Builder\ConfigBuilderGenerator;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\Compiler\ResolveInstanceofConditionalsPass;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\ContainerInterface;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use FantassinCoreWordPressVendor\Symfony\Component\Config\ConfigCache;
use FantassinCoreWordPressVendor\Symfony\Component\Config\ConfigCacheInterface;
use FantassinCoreWordPressVendor\Symfony\Component\Config\FileLocator;

use function dirname;
use function is_file;
use function is_null;
use function get_class;

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

        if (is_file($file)) {
            require_once $file;
            $container = new $classname();
        }

        return $container;
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
     * TODO: To move in DependencyInjection/Extension
     */
    public function wordPressContainerBuilder(ContainerBuilder $containerBuilder): ContainerBuilder
    {
        $containerBuilder->registerForAutoconfiguration(RegistryInterface::class)
            ->setPublic(true);

        $containerBuilder->registerForAutoconfiguration(PostTypeInterface::class)
            ->addTag('wordpress.post_type');

        $containerBuilder->registerForAutoconfiguration(TaxonomyInterface::class)
            ->addTag('wordpress.taxonomy');

        $containerBuilder->registerForAutoconfiguration(BlockInterface::class)
            ->addTag('wordpress.block');

        $containerBuilder->registerForAutoconfiguration(BlockStyleInterface::class)
            ->addTag('wordpress.block_style');

        $containerBuilder->registerForAutoconfiguration(HookInterface::class)
            ->addTag('wordpress.hook');

        $containerBuilder = $this->replaceResolveInstanceofConditionalsPass($containerBuilder);

        $containerBuilder->addCompilerPass(new RegisterPostTypePass());
        $containerBuilder->addCompilerPass(new RegisterTaxonomyPass());
        $containerBuilder->addCompilerPass(new RegisterBlockPass());
        $containerBuilder->addCompilerPass(new RegisterBlockStylePass());
        $containerBuilder->addCompilerPass(new RegisterHookPass());

        return $containerBuilder;
    }


    /**
     * @param ContainerBuilder $containerBuilder
     *
     * @throws Exception
     */
    protected function loadServices(ContainerBuilder $containerBuilder)
    {
        $configBuilderGenerator = ConfigBuilderGenerator::class ? new ConfigBuilderGenerator(
            $this->getBuildDir()
        ) : null;
        // TODO: refactor with bundles.
        $fileLocator = new FileLocator(__DIR__);
        $loader = new PhpFileLoader($containerBuilder, $fileLocator, $this->getEnvironment(), $configBuilderGenerator);
        $loader->load('Resources/config/services.php');

        // Get configuration.
        $fileLocator = new FileLocator($this->getProjectDir());
        $loader = new PhpFileLoader($containerBuilder, $fileLocator, $this->getEnvironment(), $configBuilderGenerator);
        $loader->load('config/services.php');
    }

    /**
     * @param ContainerBuilder $containerBuilder
     *
     * @return ContainerBuilder
     */
    private function replaceResolveInstanceofConditionalsPass(ContainerBuilder $containerBuilder): ContainerBuilder
    {
        $beforeOptimizationPasses = $containerBuilder->getCompilerPassConfig()->getBeforeOptimizationPasses();

        // Remove ResolveInstanceofConditionalsPass because of substr_replace() L97;
        $beforeOptimizationPasses = array_filter(
            $beforeOptimizationPasses,
            function (CompilerPassInterface $compilerPass) {
                return (get_class($compilerPass) !== ResolveInstanceofConditionalsPass::class);
            }
        );

        $containerBuilder->getCompilerPassConfig()->setBeforeOptimizationPasses($beforeOptimizationPasses);
        $containerBuilder->addCompilerPass(new ResolveInstanceOfContiditionalPassWithVendorPrefix());

        return $containerBuilder;
    }

}
