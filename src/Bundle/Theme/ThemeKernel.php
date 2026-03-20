<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Theme;

use BackTo\Framework\Compose\AbstractKernel;

final class ThemeKernel extends AbstractKernel
{
    protected function getDirectoryParameterName(): string
    {
        return 'themeDirectory';
    }

    protected function getTextDomainParameterName(): string
    {
        return 'themeTextDomain';
    }

    protected function getKernelConfigDir(): string
    {
        return __DIR__ . '/Resources/config';
    }
}
