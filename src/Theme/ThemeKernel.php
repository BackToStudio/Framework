<?php

namespace Fantassin\Core\WordPress\Theme;

use Exception;
use Fantassin\Core\WordPress\Compose\TextDomain;
use Fantassin\Core\WordPress\Compose\WordPressContainer;
use FantassinCoreWordPressVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

abstract class ThemeKernel
{

    use TextDomain;
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

        $containerBuilder->setParameter('themeDirectory', $this->getProjectDir() );
        $containerBuilder->setParameter('themeTextDomain', $this->getTextDomain() );

        $this->loadServices($containerBuilder);

        return $this->wordPressContainerBuilder($containerBuilder);
    }
}
