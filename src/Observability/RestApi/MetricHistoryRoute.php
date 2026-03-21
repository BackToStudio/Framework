<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\RestApi;

use BackTo\Framework\Observability\Contracts\MetricStoreInterface;
use BackTo\Framework\RestApi\Contracts\RestRouteInterface;
use WP_REST_Response;

/**
 * REST endpoint for metric history.
 *
 * GET /backto/v1/ops/metrics/(?P<name>[a-zA-Z0-9._-]+)
 *
 * Returns historical data points for a specific metric.
 * Requires manage_options capability.
 */
final class MetricHistoryRoute implements RestRouteInterface
{
    private readonly MetricStoreInterface $metricStore;

    public function __construct(MetricStoreInterface $metricStore)
    {
        $this->metricStore = $metricStore;
    }

    public function getNamespace(): string
    {
        return 'backto/v1';
    }

    public function getRoute(): string
    {
        return '/ops/metrics/(?P<name>[a-zA-Z0-9._-]+)';
    }

    public function getMethods(): array
    {
        return ['GET'];
    }

    public function handle(mixed $request): mixed
    {
        $name = (string) $request['name'];
        $since = (int) ($request['since'] ?? (\time() - 86400));
        $until = (int) ($request['until'] ?? 0);

        $history = $this->metricStore->history($name, $since, $until);
        $latest = $this->metricStore->latest($name);

        if ($history === [] && $latest === null) {
            return new WP_REST_Response([
                'error' => 'Metric not found: ' . $name,
            ], 404);
        }

        return new WP_REST_Response([
            'name' => $name,
            'latest' => $latest,
            'history' => $history,
            'count' => \count($history),
        ], 200);
    }

    public function getPermissionCallback(): ?callable
    {
        return static fn(): bool => \current_user_can('manage_options');
    }
}
