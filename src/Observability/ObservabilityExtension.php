<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability;

use BackTo\Framework\Compose\AbstractExtension;
use BackTo\Framework\Observability\Alert\AlertDispatcher;
use BackTo\Framework\Observability\Alert\EmailAlertChannel;
use BackTo\Framework\Observability\Alert\LogAlertChannel;
use BackTo\Framework\Observability\Contracts\AlertChannelInterface;
use BackTo\Framework\Observability\Contracts\AlertDispatcherInterface;
use BackTo\Framework\Observability\Contracts\ErrorHandlerInterface;
use BackTo\Framework\Contracts\HealthCheckInterface;
use BackTo\Framework\Contracts\LoggerInterface;
use BackTo\Framework\Observability\Contracts\MetricStoreInterface;
use BackTo\Framework\Observability\Contracts\PerformanceCollectorInterface;
use BackTo\Framework\Observability\DependencyInjection\Compiler\RegisterAlertChannelPass;
use BackTo\Framework\Observability\DependencyInjection\Compiler\RegisterHealthCheckPass;
use BackTo\Framework\Observability\Infrastructure\WordPressLogger;
use BackTo\Framework\Observability\Infrastructure\WordPressMetricStore;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;

final class ObservabilityExtension extends AbstractExtension
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Observability\\',
            'exclude' => '{DependencyInjection,Tests,Contracts,Infrastructure,HealthCheck,Alert,Dashboard}',
        ];
    }

    public function register(ContainerBuilder $containerBuilder): void
    {
        // Health checks
        $containerBuilder->registerForAutoconfiguration(HealthCheckInterface::class)
            ->addTag('wordpress.health_check');
        $containerBuilder->addCompilerPass(new RegisterHealthCheckPass());

        // Alert channels
        $containerBuilder->registerForAutoconfiguration(AlertChannelInterface::class)
            ->addTag('observability.alert_channel');
        $containerBuilder->addCompilerPass(new RegisterAlertChannelPass());

        // Logger
        $containerBuilder->register(LoggerInterface::class, WordPressLogger::class);
        $containerBuilder->setAlias(WordPressLogger::class, LoggerInterface::class);

        // Error handler
        $containerBuilder->register(ErrorHandlerInterface::class, ErrorHandler::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(ErrorHandler::class, ErrorHandlerInterface::class);

        // Performance collector
        $containerBuilder->register(PerformanceCollectorInterface::class, PerformanceCollector::class);
        $containerBuilder->setAlias(PerformanceCollector::class, PerformanceCollectorInterface::class);

        // Metric store
        $containerBuilder->register(MetricStoreInterface::class, WordPressMetricStore::class);
        $containerBuilder->setAlias(WordPressMetricStore::class, MetricStoreInterface::class);

        // Alert dispatcher
        $containerBuilder->register(AlertDispatcherInterface::class, AlertDispatcher::class)
            ->setAutowired(true);
        $containerBuilder->setAlias(AlertDispatcher::class, AlertDispatcherInterface::class);

        // Alert channels
        $containerBuilder->register(LogAlertChannel::class)
            ->setAutowired(true)
            ->addTag('observability.alert_channel');
        $containerBuilder->register(EmailAlertChannel::class)
            ->setAutowired(true)
            ->addTag('observability.alert_channel');

        // Dashboard page (excluded from auto-discovery)
        $containerBuilder->register(Dashboard\OperationsDashboardRenderer::class);
        $containerBuilder->register(Dashboard\OperationsDashboardPage::class)
            ->setAutowired(true)
            ->addTag('wordpress.admin_page')
            ->addTag('wordpress.hook');
    }

    public function getDefaultConfiguration(): array
    {
        return ObservabilityConfiguration::getDefaults();
    }
}
