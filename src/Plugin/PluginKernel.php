<?php

namespace Fantassin\Core\WordPress\Plugin;

use Fantassin\Core\WordPress\Contracts\RegistryInterface;
use Fantassin\Core\WordPress\Hooks\HookRegistry;
use Fantassin\Core\WordPress\Contracts\HookInterface;
use Fantassin\Core\WordPress\Contracts\BlockInterface;
use Fantassin\Core\WordPress\Hooks\DependencyInjection\Compiler\RegisterHookPass;
use Fantassin\Core\WordPress\Blocks\DependencyInjection\Compiler\RegisterBlockPass;
use Fantassin\Core\WordPress\PostType\PostTypeInterface;
use Symfony\Component\Config\ConfigCache;
use Symfony\Component\Config\ConfigCacheInterface;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\Dumper\PhpDumper;
use Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

abstract class PluginKernel
{

    /**
     * @var string
     */
    protected $environnement;

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

    public function __construct(string $environnement, bool $debug)
    {
        $this->environnement = $environnement;
        $this->debug = $debug;
    }

    public function getPluginFile()
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
     * @throws \Exception
     */
    public function getContainer()
    {
        $file = $this->getPluginDir() . '/var/container.php';
        $containerConfigCache = new ConfigCache($file, $this->isDebug());

        if (!$containerConfigCache->isFresh()) {
            $containerBuilder = $this->getContainerBuilder();
            $containerBuilder->compile();
            $this->dumpContainer($containerConfigCache, $containerBuilder, 'CachedContainer');
        }

        if (is_file($file)) {
            require_once $file;
            $container = new \CachedContainer();
        }

        return $container;
    }

    /**
     * @return string
     */
    public function getEnvironnement(): string
    {
        return $this->environnement;
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
        } catch (\Exception $e) {
            if ($this->isDebug()) {
                throw $e;
            }
            // Don't crash the entire site, simply don't load.
        }
    }

    abstract public function getPluginName(): string;

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
        string $classname
    ) {
        $dumper = new PhpDumper($containerBuilder);
        $configCache->write(
            $dumper->dump(
                [
                    'class' => $classname,
                    'debug' => $this->isDebug()
                ]
            ),
            $containerBuilder->getResources()
        );
    }

    /**
     * Prepare Container settings.
     *
     * @return ContainerBuilder
     * @throws \Exception
     */
    private function getContainerBuilder(): ContainerBuilder
    {
        $containerBuilder = new ContainerBuilder();

        $this->loadServices($containerBuilder);

//        $containerBuilder->setParameter('plugin.info', \get_plugin_data($this->getPluginFile()));

        /**
         * TODO: To move in DependencyInjection/Extension
         */

        $containerBuilder->registerForAutoconfiguration(RegistryInterface::class)
            ->setPublic(true);

        $containerBuilder->registerForAutoconfiguration(PostTypeInterface::class)
            ->addTag('wordpress.post_type');

        $containerBuilder->registerForAutoconfiguration(BlockInterface::class)
            ->addTag('wordpress.block');

        $containerBuilder->registerForAutoconfiguration(HookInterface::class)
            ->addTag('wordpress.hooks');

        $containerBuilder->addCompilerPass(new RegisterHookPass());
        $containerBuilder->addCompilerPass(new RegisterBlockPass());

        return $containerBuilder;
    }

    private function loadServices(ContainerBuilder $containerBuilder)
    {
        $loader = new PhpFileLoader($containerBuilder, new FileLocator($this->getPluginDir()));
        $loader->load('config/services.php');

        $loader = new PhpFileLoader($containerBuilder, new FileLocator(__DIR__));
        $loader->load('Resources/config/services.php');
    }
}
