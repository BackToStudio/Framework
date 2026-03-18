<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability;

use BackTo\Framework\Contracts\ExtensionInterface;
use BackTo\Framework\Observability\Contracts\ErrorHandlerInterface;
use BackTo\Framework\Observability\Contracts\HealthCheckInterface;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Observability\Contracts\PerformanceCollectorInterface;
use BackTo\Framework\Observability\DependencyInjection\Compiler\RegisterHealthCheckPass;
use BackTo\Framework\Observability\Infrastructure\WordPressLogger;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

final class ObservabilityExtension implements ExtensionInterface
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Observability\\',
            'exclude' => '{DependencyInjection,Tests,Contracts,Infrastructure,HealthCheck}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        $containerBuilder->registerForAutoconfiguration(HealthCheckInterface::class)
            ->addTag('wordpress.health_check');

        $containerBuilder->addCompilerPass(new RegisterHealthCheckPass());

        $containerBuilder->register(LoggerInterface::class, WordPressLogger::class);
        $containerBuilder->setAlias(WordPressLogger::class, LoggerInterface::class);

        $containerBuilder->register(ErrorHandlerInterface::class, ErrorHandler::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(ErrorHandler::class, ErrorHandlerInterface::class);

        $containerBuilder->register(PerformanceCollectorInterface::class, PerformanceCollector::class);
        $containerBuilder->setAlias(PerformanceCollector::class, PerformanceCollectorInterface::class);
    }

    public function getDefaultConfiguration(): array
    {
        return ObservabilityConfiguration::getDefaults();
    }
}
