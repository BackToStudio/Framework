<?php

namespace BackTo\Framework\Compose;

use Exception;
use BackToVendor\Symfony\Component\Config\Builder\ConfigBuilderGenerator;
use BackToVendor\Symfony\Component\Config\FileLocator;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use BackToVendor\Symfony\Component\DependencyInjection\Loader\PhpFileLoader;

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
     * Return the path to this kernel's Resources/config directory.
     */
    abstract protected function getKernelConfigDir(): string;

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

        return $this->configureWordPressContainer($containerBuilder);
    }

    /**
     * Load kernel-specific services (I18n, bindings, etc.).
     */
    protected function loadKernelServices(ContainerBuilder $containerBuilder, ?ConfigBuilderGenerator $configBuilderGenerator): void
    {
        $configDir = $this->getKernelConfigDir();
        $fileLocator = new FileLocator($configDir);
        $loader = new PhpFileLoader($containerBuilder, $fileLocator, $this->getEnvironment(), $configBuilderGenerator);
        $loader->load('services.php');
    }
}
