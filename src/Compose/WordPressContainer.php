<?php


namespace Fantassin\Core\WordPress\Compose;


use Exception;
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
use ReflectionObject;
use FantassinCoreWordPressVendor\Symfony\Component\Config\ConfigCache;
use FantassinCoreWordPressVendor\Symfony\Component\Config\ConfigCacheInterface;
use FantassinCoreWordPressVendor\Symfony\Component\Config\FileLocator;

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
        if (null === $this->kernelFile) {
            $reflected = new ReflectionObject($this);
            $this->kernelFile = $reflected->getFileName();
        }

        return $this->kernelFile;
    }

    /**
     * Get Kernel directory from root Kernel instanciation.
     *
     * @return string
     */
    public function getKernelDir(): string
    {
        if (null === $this->kernelDir) {
            $this->kernelDir = \dirname($this->getKernelFile());
        }

        return $this->kernelDir;
    }

    /**
     * @return string
     */
    public function getBuildDir(): string
    {
        return $this->getKernelDir() . '/var/';
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
     * @param string $file
     *
     * @return ContainerInterface|null
     */
    public function generateContainer(string $file): ?ContainerInterface
    {
        $cache = new ConfigCache($file, $this->isDebug());
        $classname = 'Container' . ContainerBuilder::hash($this->getKernelFile());

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
        $classname = $containerBaseClass . ContainerBuilder::hash($this->getKernelFile());
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
        $configBuilderGenerator = ConfigBuilderGenerator::class ? new ConfigBuilderGenerator($this->get) : null;
        $loader = new PhpFileLoader($containerBuilder, new FileLocator(__DIR__), $this->getEnvironment(), null);
        $loader->load('Resources/config/services.php');

        $fileLocator = new FileLocator($this->getKernelDir());
        $loader = new PhpFileLoader($containerBuilder, $fileLocator);
        $loader->load('config/services.php');

        // $allowedblockJson = $fileLocator->locate( 'config/allowed_blocks.json' );
        // $allowedBlocks = json_decode($allowedblockJson, true);

        // $containerBuilder->setParameter('allowedBlocks', $allowedBlocks);

    }

    /**
     * @param ContainerBuilder $containerBuilder
     *
     * @return ContainerBuilder
     */
    private function replaceResolveInstanceofConditionalsPass(ContainerBuilder $containerBuilder): ContainerBuilder
    {
        $beforeOptimizationPasses = $containerBuilder->getCompilerPassConfig()->getBeforeOptimizationPasses();

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
