<?php

namespace BackTo\Framework\Theme;

use Exception;
use BackTo\Framework\Compose\TextDomain;
use BackTo\Framework\Compose\WordPressContainer;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

class ThemeKernel
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
