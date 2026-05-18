<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation;

use BackTo\Framework\Compose\AbstractExtension;
use BackTo\Framework\Validation\Contracts\ValidatedRestRouteInterface;
use BackTo\Framework\Validation\Contracts\ValidatorInterface;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

final class ValidationExtension extends AbstractExtension
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Validation\\',
            'exclude' => '{Tests,Contracts,Constraint,DependencyInjection}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->register(ValidatorInterface::class, Validator::class);
        $containerBuilder->setAlias(Validator::class, ValidatorInterface::class);

        $containerBuilder->registerForAutoconfiguration(ValidatedRestRouteInterface::class)
            ->addTag('wordpress.rest_route');
    }
}
