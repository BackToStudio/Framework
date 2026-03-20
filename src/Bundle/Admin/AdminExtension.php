<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Admin;

use BackTo\Framework\Contracts\AdminPageInterface;
use BackTo\Framework\Bundle\Admin\Contracts\AdminPageRegistrarInterface;
use BackTo\Framework\Bundle\Admin\DependencyInjection\Compiler\RegisterAdminPagePass;
use BackTo\Framework\Bundle\Admin\Infrastructure\WordPressAdminPageRegistrar;
use BackTo\Framework\Compose\AbstractExtension;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

final class AdminExtension extends AbstractExtension
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Bundle\\Admin\\',
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
