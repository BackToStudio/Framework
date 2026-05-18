<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\RestApi;

use BackTo\Framework\Observability\Contracts\MetricStoreInterface;
use BackTo\Framework\Observability\HealthCheckRegistry;
use BackTo\Framework\RestApi\Contracts\RestRequest;
use BackTo\Framework\RestApi\Contracts\RestResponse;
use BackTo\Framework\Validation\Constraint\Type;
use BackTo\Framework\Validation\Contracts\ValidatedRestRouteInterface;

/**
 * REST endpoint for operational metrics.
 *
 * GET /backto/v1/ops/metrics
 *
 * Returns health check results, metric summary, and system status.
 * Requires manage_options capability.
 */
final class MetricsRoute implements ValidatedRestRouteInterface
{
    private const SUMMARY_WINDOW = 86400; // 24 hours

    private readonly HealthCheckRegistry $healthCheckRegistry;
    private readonly MetricStoreInterface $metricStore;

    public function __construct(
        HealthCheckRegistry $healthCheckRegistry,
        MetricStoreInterface $metricStore,
    ) {
        $this->healthCheckRegistry = $healthCheckRegistry;
        $this->metricStore = $metricStore;
    }

    public function getNamespace(): string
    {
        return 'backto/v1';
    }

    public function getRoute(): string
    {
        return '/ops/metrics';
    }

    public function getMethods(): array
    {
        return ['GET'];
    }

    public function rules(): array
    {
        return [
            'since' => new Type('numeric'),
        ];
    }

    public function handle(RestRequest $request): RestResponse
    {
        $since = (int) ($request->getParam('since') ?? (\time() - self::SUMMARY_WINDOW));

        $healthResults = $this->healthCheckRegistry->runAll();
        $healthData = [];
        foreach ($healthResults as $name => $result) {
            $healthData[$name] = $result->toArray();
        }

        $summary = $this->metricStore->summary($since);

        return new RestResponse([
            'health_checks' => $healthData,
            'metrics_summary' => $summary,
            'period_start' => \gmdate('Y-m-d\TH:i:s\Z', $since),
            'period_end' => \gmdate('Y-m-d\TH:i:s\Z'),
        ], 200);
    }

    public function getPermissionCallback(): ?callable
    {
        return static fn(): bool => \current_user_can('manage_options');
    }
}
