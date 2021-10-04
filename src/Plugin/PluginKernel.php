<?php

namespace Fantassin\Core\WordPress\Plugin;

use Exception;
use Fantassin\Core\WordPress\Blocks\DependencyInjection\Compiler\RegisterBlockStylePass;
use Fantassin\Core\WordPress\Blocks\RegisterBlockStyles;
use Fantassin\Core\WordPress\Contracts\BlockStyleInterface;
use Fantassin\Core\WordPress\Contracts\RegistryInterface;
use Fantassin\Core\WordPress\Hooks\HookRegistry;
use Fantassin\Core\WordPress\Contracts\HookInterface;
use Fantassin\Core\WordPress\Contracts\BlockInterface;
use Fantassin\Core\WordPress\Hooks\DependencyInjection\Compiler\RegisterHookPass;
use Fantassin\Core\WordPress\Blocks\DependencyInjection\Compiler\RegisterBlockPass;
use Fantassin\Core\WordPress\PostType\DependencyInjection\Compiler\RegisterPostTypePass;
use Fantassin\Core\WordPress\PostType\PostTypeInterface;
use Fantassin\Core\WordPress\Taxonomy\DependencyInjection\Compiler\RegisterTaxonomyPass;
use Fantassin\Core\WordPress\Taxonomy\TaxonomyInterface;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\FileLocator;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\ContainerInterface;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

abstract class PluginKernel
{

    /**
     * @var string
     */
    protected $environment;

    /**
     * @var bool
     */
    protected $debug;

    /**
     * @var string
     */
    protected $pluginDir;

    /**
     * @var string
     */
    protected $pluginFile;

    /**
     * @param string $environment
     * @param bool $debug
     */
    public function __construct(string $environment, bool $debug)
    {
        $this->environment = $environment;
        $this->debug = $debug;
    }

    /**
     * @return string
     */
    public function getPluginFile(): string
    {
        if (null === $this->pluginFile) {
            $reflected = new \ReflectionObject($this);
            $this->pluginFile = $reflected->getFileName();
        }
        return $this->pluginFile;
    }

    /**
     * Get Plugin directory from root plugin instanciation.
     *
     * @return string
     */
    public function getPluginDir(): string
    {
        if (null === $this->pluginDir) {
            $this->pluginDir = \dirname($this->getPluginFile());
        }

        return $this->pluginDir;
    }

    /**
     * @return ContainerInterface|null
     * @throws Exception
     */
    public function getContainer()
    {
        $file = $this->getPluginDir() . '/var/container.php';
        $containerConfigCache = new ConfigCache($file, $this->isDebug());
        $classname = 'Container' . md5($this->getPluginFile());

        if (!$containerConfigCache->isFresh()) {
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
     * @return string
     */
    public function getEnvironment(): string
    {
        return $this->environment;
    }

    /**
     * @return bool
     */
    public function isDebug(): bool
    {
        return $this->debug;
    }

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
     * @deprecated
     * @return string
     */
    public function getPluginName(): string {
        trigger_error('getPluginName() method is deprecated use getPluginTextDomain() method instead.', E_USER_DEPRECATED);
        return $this->getPluginTextDomain();
    }

    /**
     * @return string
     */
    abstract public function getPluginTextDomain(): string;

    /**
     * Store Container in PHP version.
     *
     * @param ConfigCacheInterface $configCache
     * @param ContainerBuilder $containerBuilder
     * @param string $classname
     */
    private function dumpContainer(
        ConfigCacheInterface $configCache,
        ContainerBuilder $containerBuilder,
        array $options
    ) {
        $dumper = new PhpDumper($containerBuilder);
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
     * Prepare Container settings.
     *
     * @return ContainerBuilder
     * @throws Exception
     */
    private function getContainerBuilder(): ContainerBuilder
    {
        $containerBuilder = new ContainerBuilder();

        $this->loadServices($containerBuilder);

        $containerBuilder->setParameter('$pluginDirectory', $this->getPluginDir());
        $containerBuilder->setParameter('$pluginTextDomain', $this->getPluginTextDomain());

        /**
         * TODO: To move in DependencyInjection/Extension
         */
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
    private function loadServices(ContainerBuilder $containerBuilder)
    {
        $loader = new PhpFileLoader($containerBuilder, new FileLocator(__DIR__));
        $loader->load('Resources/config/services.php');

        $fileLocator = new FileLocator($this->getPluginDir());
        $loader = new PhpFileLoader($containerBuilder, $fileLocator);
        $loader->load('config/services.php');

        $allowedblockJson = $fileLocator->locate( 'config/allowed_blocks.json' );
        $allowedBlocks = json_decode($allowedblockJson, true);

        $containerBuilder->setParameter('allowedBlocks', $allowedBlocks);

    }
}
