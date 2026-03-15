<?php

declare(strict_types=1);

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

    abstract protected function getDirectoryParameterName(): string;

    abstract protected function getTextDomainParameterName(): string;

    abstract protected function getKernelConfigDir(): string;

    /**
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

    protected function loadKernelServices(ContainerBuilder $containerBuilder, ConfigBuilderGenerator $configBuilderGenerator): void
    {
        $configDir = $this->getKernelConfigDir();
        $fileLocator = new FileLocator($configDir);
        $loader = new PhpFileLoader($containerBuilder, $fileLocator, $this->getEnvironment(), $configBuilderGenerator);
        $loader->load('services.php');
    }
}
