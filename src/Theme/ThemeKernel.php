<?php

namespace BackTo\Framework\Theme;

use BackTo\Framework\Compose\AbstractKernel;

class ThemeKernel extends AbstractKernel
{
    protected function getDirectoryParameterName(): string
    {
        return 'themeDirectory';
    }

    protected function getTextDomainParameterName(): string
    {
        return 'themeTextDomain';
    }
}
