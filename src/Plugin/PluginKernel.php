<?php

namespace BackTo\Framework\Plugin;

use BackTo\Framework\Compose\AbstractKernel;

class PluginKernel extends AbstractKernel
{
    protected function getDirectoryParameterName(): string
    {
        return 'pluginDirectory';
    }

    protected function getTextDomainParameterName(): string
    {
        return 'pluginTextDomain';
    }

    protected function getKernelConfigDir(): string
    {
        return __DIR__ . '/Resources/config';
    }
}
