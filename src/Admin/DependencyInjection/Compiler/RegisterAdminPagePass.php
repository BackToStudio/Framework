<?php

declare(strict_types=1);

namespace BackTo\Framework\Admin\DependencyInjection\Compiler;

use BackTo\Framework\Admin\AdminPageRegistry;
use BackTo\Framework\Compose\DependencyInjection\Compiler\AbstractTaggedServiceCompilerPass;

class RegisterAdminPagePass extends AbstractTaggedServiceCompilerPass
{
    protected function getRegistryClass(): string
    {
        return AdminPageRegistry::class;
    }

    protected function getTag(): string
    {
        return 'wordpress.admin_page';
    }
}
