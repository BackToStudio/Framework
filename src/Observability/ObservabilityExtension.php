<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability;

use BackTo\Framework\Cache\CacheMetricsDecorator;
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
use BackTo\Framework\Observability\Contracts\RemediationInterface;
use BackTo\Framework\Observability\DependencyInjection\Compiler\RegisterAlertChannelPass;
use BackTo\Framework\Observability\DependencyInjection\Compiler\RegisterHealthCheckPass;
use BackTo\Framework\Observability\DependencyInjection\Compiler\RegisterRemediationPass;
use BackTo\Framework\Observability\Infrastructure\WordPressLogger;
use BackTo\Framework\Observability\Infrastructure\WordPressMetricStore;
use BackTo\Framework\Observability\Remediation\CacheRemediation;
use BackTo\Framework\Observability\Remediation\DatabaseRemediation;
use BackTo\Framework\Observability\Remediation\QueueRemediation;
use BackTo\Framework\Observability\RestApi\MetricHistoryRoute;
use BackTo\Framework\Observability\RestApi\MetricsRoute;
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
use BackToVendor\Symfony\Component\DependencyInjection\Reference;

final class ObservabilityExtension extends AbstractExtension
{
    public function getBundle(): ?array
    {
        return [
            'dir' => __DIR__,
            'namespace' => 'BackTo\\Framework\\Observability\\',
            'exclude' => '{DependencyInjection,Tests,Contracts,Infrastructure,HealthCheck,Alert,Dashboard,Remediation,RestApi}',
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

        // Auto-remediation handlers
        $containerBuilder->registerForAutoconfiguration(RemediationInterface::class)
            ->addTag('observability.remediation');
        $containerBuilder->addCompilerPass(new RegisterRemediationPass());

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

        // Query monitor (slow query detection)
        $containerBuilder->register(Contracts\QueryMonitorInterface::class, Infrastructure\WordPressQueryMonitor::class);
        $containerBuilder->setAlias(Infrastructure\WordPressQueryMonitor::class, Contracts\QueryMonitorInterface::class);
        $containerBuilder->register(SlowQueryMonitor::class)
            ->setAutowired(true);

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

        // Auto-remediation
        $containerBuilder->register(AutoRemediation::class)
            ->setAutowired(true);
        $containerBuilder->register(QueueRemediation::class)
            ->setAutowired(true)
            ->addTag('observability.remediation');
        $containerBuilder->register(CacheRemediation::class)
            ->setAutowired(true)
            ->addTag('observability.remediation');
        $containerBuilder->register(DatabaseRemediation::class)
            ->setAutowired(true)
            ->addTag('observability.remediation');

        // Trend analyzer
        $containerBuilder->register(TrendAnalyzer::class)
            ->setAutowired(true);

        // Cache metrics decorator
        $containerBuilder->register(CacheMetricsDecorator::class)
            ->setAutowired(true);

        // Flush metric buffer on shutdown (cache metrics + metric store)
        $containerBuilder->register(FlushMetricsOnShutdown::class)
            ->setAutowired(true)
            ->addMethodCall('setCacheMetricsDecorator', [new Reference(CacheMetricsDecorator::class)]);

        // Dashboard widgets (excluded from auto-discovery)
        $containerBuilder->register(Dashboard\Widget\HealthCheckWidget::class);
        $containerBuilder->register(Dashboard\Widget\QueueStatusWidget::class);
        $containerBuilder->register(Dashboard\Widget\CacheMetricsWidget::class);
        $containerBuilder->register(Dashboard\Widget\SecurityAlertsWidget::class)
            ->setAutowired(true);
        $containerBuilder->register(Dashboard\OperationsDashboardRenderer::class)
            ->setAutowired(true);
        $containerBuilder->register(Dashboard\OperationsDashboardPage::class)
            ->setAutowired(true)
            ->addTag('wordpress.admin_page')
            ->addTag('wordpress.hook');

        // REST API routes (excluded from auto-discovery)
        $containerBuilder->register(MetricsRoute::class)
            ->setAutowired(true)
            ->addTag('wordpress.rest_route');
        $containerBuilder->register(MetricHistoryRoute::class)
            ->setAutowired(true)
            ->addTag('wordpress.rest_route');
    }

    public function getDefaultConfiguration(): array
    {
        return ObservabilityConfiguration::getDefaults();
    }
}
