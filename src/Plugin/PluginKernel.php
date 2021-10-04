<?php

namespace Fantassin\Core\WordPress\Plugin;

use Exception;
use Fantassin\Core\WordPress\Compose\WordPressContainer;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\ContainerInterface;
use ReflectionObject;

abstract class PluginKernel
{

    use WordPressContainer;

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
            $reflected = new ReflectionObject($this);
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

        return $this->generateContainer($file);
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

        return $this->wordPressContainerBuilder($containerBuilder);
    }
}
