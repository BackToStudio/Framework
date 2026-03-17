<?php

declare(strict_types=1);

namespace BackTo\Framework\Admin;

use BackTo\Framework\Admin\Contracts\AdminPageInterface;
use BackTo\Framework\Admin\Contracts\AdminPageRegistrarInterface;
use BackTo\Framework\Admin\DependencyInjection\Compiler\RegisterAdminPagePass;
use BackTo\Framework\Admin\Infrastructure\WordPressAdminPageRegistrar;
use BackTo\Framework\Contracts\ExtensionInterface;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

final class AdminExtension implements ExtensionInterface
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Admin\\',
            'exclude' => '{DependencyInjection,Tests,Contracts,Infrastructure}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->registerForAutoconfiguration(AdminPageInterface::class)
            ->addTag('wordpress.admin_page');

        $containerBuilder->addCompilerPass(new RegisterAdminPagePass());

        $containerBuilder->register(AdminPageRegistrarInterface::class, WordPressAdminPageRegistrar::class);
        $containerBuilder->setAlias(WordPressAdminPageRegistrar::class, AdminPageRegistrarInterface::class);
    }

    public function getDefaultConfiguration(): array
    {
        return [];
    }
}
