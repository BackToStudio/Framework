<?php

namespace Fantassin\Core\WordPress\Theme;

use Exception;
use Fantassin\Core\WordPress\Compose\HasTextDomain;
use Fantassin\Core\WordPress\Compose\WordPressContainer;
use FantassinCoreWordPressVendor\Symfony\Component\Config\FileLocator;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\ContainerInterface;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

abstract class ThemeKernel
{

    use HasTextDomain;
    use WordPressContainer;

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
    abstract public function getTextDomain(): string;

    /**
     * Prepare Container settings.
     *
     * @return ContainerBuilder
     * @throws Exception
     */
    private function getContainerBuilder(): ContainerBuilder
    {
        $containerBuilder = new ContainerBuilder();

        $containerBuilder->setParameter('themeDirectory', $this->getKernelDir() );
        $containerBuilder->setParameter('themeTextDomain', $this->getTextDomain() );

        $this->loadServices($containerBuilder);

        return $this->wordPressContainerBuilder($containerBuilder);
    }
}
