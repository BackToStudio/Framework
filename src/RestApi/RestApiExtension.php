<?php

declare(strict_types=1);

namespace BackTo\Framework\RestApi;

use BackTo\Framework\Contracts\ExtensionInterface;
use BackTo\Framework\RestApi\Contracts\RestRouteInterface;
use BackTo\Framework\RestApi\Contracts\RestRouteRegistrarInterface;
use BackTo\Framework\RestApi\DependencyInjection\Compiler\RegisterRestRoutePass;
use BackTo\Framework\RestApi\Infrastructure\WordPressRestRouteRegistrar;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

final class RestApiExtension implements ExtensionInterface
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\RestApi\\',
            'exclude' => '{DependencyInjection,Tests,Contracts,Infrastructure}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->registerForAutoconfiguration(RestRouteInterface::class)
            ->addTag('wordpress.rest_route');

        $containerBuilder->addCompilerPass(new RegisterRestRoutePass());

        $containerBuilder->register(RestRouteRegistrarInterface::class, WordPressRestRouteRegistrar::class);
        $containerBuilder->setAlias(WordPressRestRouteRegistrar::class, RestRouteRegistrarInterface::class);
    }

    public function getDefaultConfiguration(): array
    {
        return RestApiConfiguration::getDefaults();
    }
}
