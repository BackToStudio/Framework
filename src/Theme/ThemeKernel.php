<?php

namespace Fantassin\Core\WordPress\Theme;

use Exception;
use Fantassin\Core\WordPress\Compose\WordPressContainer;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\ContainerInterface;

abstract class ThemeKernel
{

    use WordPressContainer;

    /**
     * @var string
     */
    protected $themeDir;

    /**
     * @var string
     */
    protected $themeFile;

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
    public function getThemeFile(): string
    {
        if (null === $this->themeFile) {
            $reflected = new \ReflectionObject($this);
            $this->themeFile = $reflected->getFileName();
        }
        return $this->themeFile;
    }

    /**
     * Get Plugin directory from root plugin instanciation.
     *
     * @return string
     */
    public function getThemeDir(): string
    {
        if (null === $this->themeDir) {
            $this->themeDir = \dirname($this->getThemeFile());
        }

        return $this->themeDir;
    }

    /**
     * @return ContainerInterface|null
     * @throws Exception
     */
    public function getContainer()
    {
        $file = $this->getThemeDir() . '/var/container.php';

        return $this->generateContainer($file);
    }

    /**
     * @return string
     */
    abstract public function getThemeTextDomain(): string;

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

        $containerBuilder->setParameter('$themeDirectory', $this->getThemeDir());
        $containerBuilder->setParameter('$themeTextDomain', $this->getThemeTextDomain());

        return $this->wordPressContainerBuilder($containerBuilder);
    }
}
