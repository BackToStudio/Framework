<?php

namespace BackTo\Framework\Plugin;

use Exception;
use BackTo\Framework\Compose\TextDomain;
use BackTo\Framework\Compose\WordPressContainer;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

abstract class PluginKernel
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

        $containerBuilder->setParameter('pluginDirectory', $this->getProjectDir());
        $containerBuilder->setParameter('pluginTextDomain', $this->getTextDomain());

        $this->loadServices($containerBuilder);

        return $this->wordPressContainerBuilder($containerBuilder);
    }
}
