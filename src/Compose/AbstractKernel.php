<?php

namespace BackTo\Framework\Compose;

use Exception;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

abstract class AbstractKernel
{
    use TextDomain;
    use WordPressContainer;

    public function __construct(string $environment, bool $debug)
    {
        $this->environment = $environment;
        $this->debug = $debug;
    }

    /**
     * Return the parameter name for the project directory (e.g. 'themeDirectory', 'pluginDirectory').
     */
    abstract protected function getDirectoryParameterName(): string;

    /**
     * Return the parameter name for the text domain (e.g. 'themeTextDomain', 'pluginTextDomain').
     */
    abstract protected function getTextDomainParameterName(): string;

    /**
     * Prepare Container settings.
     *
     * @return ContainerBuilder
     * @throws Exception
     */
    private function getContainerBuilder(): ContainerBuilder
    {
        $containerBuilder = new ContainerBuilder();

        $containerBuilder->setParameter($this->getDirectoryParameterName(), $this->getProjectDir());
        $containerBuilder->setParameter($this->getTextDomainParameterName(), $this->getTextDomain());

        $this->loadServices($containerBuilder);

        return $this->wordPressContainerBuilder($containerBuilder);
    }
}
