<?php


namespace Fantassin\Core\WordPress\Compose;


use Exception;
use Fantassin\Core\WordPress\Blocks\DependencyInjection\Compiler\RegisterBlockPass;
use Fantassin\Core\WordPress\Blocks\DependencyInjection\Compiler\RegisterBlockStylePass;
use Fantassin\Core\WordPress\Contracts\BlockInterface;
use Fantassin\Core\WordPress\Contracts\BlockStyleInterface;
use Fantassin\Core\WordPress\Contracts\HookInterface;
use Fantassin\Core\WordPress\Contracts\RegistryInterface;
use Fantassin\Core\WordPress\Hooks\DependencyInjection\Compiler\RegisterHookPass;
use Fantassin\Core\WordPress\Hooks\HookRegistry;
use Fantassin\Core\WordPress\PostType\DependencyInjection\Compiler\RegisterPostTypePass;
use Fantassin\Core\WordPress\PostType\PostTypeInterface;
use Fantassin\Core\WordPress\Taxonomy\DependencyInjection\Compiler\RegisterTaxonomyPass;
use Fantassin\Core\WordPress\Taxonomy\TaxonomyInterface;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\Loader\PhpFileLoader;
use ReflectionObject;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\FileLocator;

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

    public function generateContainer(string $file)
    {
        $containerConfigCache = new ConfigCache($file, $this->isDebug());
        $classname            = 'Container' . md5($this->getKernelFile());

        if ( ! $containerConfigCache->isFresh()) {
            $containerBuilder = $this->getContainerBuilder();
            $containerBuilder->compile();
            $options = [
                'class' => $classname
            ];
            $this->dumpContainer($containerConfigCache, $containerBuilder, $options);
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
     * @param ConfigCacheInterface $configCache
     * @param ContainerBuilder $containerBuilder
     * @param string $classname
     */
    protected function dumpContainer(
        ConfigCacheInterface $configCache,
        ContainerBuilder $containerBuilder,
        array $options
    ) {
        $dumper  = new PhpDumper($containerBuilder);
        $options = array_merge(
            [
                'debug' => $this->isDebug()
            ],
            $options
        );
        $configCache->write(
            $dumper->dump($options),
            $containerBuilder->getResources()
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

        $containerBuilder->addCompilerPass(new RegisterPostTypePass());
        $containerBuilder->addCompilerPass(new RegisterTaxonomyPass());
        $containerBuilder->addCompilerPass(new RegisterBlockPass());
        $containerBuilder->addCompilerPass(new RegisterBlockStylePass());
        $containerBuilder->addCompilerPass(new RegisterHookPass());

        return $containerBuilder;
    }


    /**
     * @param ContainerBuilder $containerBuilder
     * @throws Exception
     */
    protected function loadServices(ContainerBuilder $containerBuilder)
    {
        $loader = new PhpFileLoader($containerBuilder, new FileLocator(__DIR__));
        $loader->load('Resources/config/services.php');

        $fileLocator = new FileLocator($this->getThemeDir());
        $loader = new PhpFileLoader($containerBuilder, $fileLocator);
        $loader->load('config/services.php');

        $allowedblockJson = $fileLocator->locate( 'config/allowed_blocks.json' );
        $allowedBlocks = json_decode($allowedblockJson, true);

        $containerBuilder->setParameter('allowedBlocks', $allowedBlocks);

    }

}
