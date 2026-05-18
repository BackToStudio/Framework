<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\RestApi;

use BackTo\Framework\Observability\Contracts\MetricStoreInterface;
use BackTo\Framework\RestApi\Contracts\RestRequest;
use BackTo\Framework\RestApi\Contracts\RestResponse;
use BackTo\Framework\Validation\Constraint\NotBlank;
use BackTo\Framework\Validation\Constraint\Regex;
use BackTo\Framework\Validation\Constraint\Type;
use BackTo\Framework\Validation\Contracts\ValidatedRestRouteInterface;

/**
 * REST endpoint for metric history.
 *
 * GET /backto/v1/ops/metrics/(?P<name>[a-zA-Z0-9._-]+)
 *
 * Returns historical data points for a specific metric.
 * Requires manage_options capability.
 */
final class MetricHistoryRoute implements ValidatedRestRouteInterface
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

    public function rules(): array
    {
        return [
            'name' => [new NotBlank(), new Regex('/^[a-zA-Z0-9._-]+$/')],
            'since' => new Type('numeric'),
            'until' => new Type('numeric'),
        ];
    }

    public function handle(RestRequest $request): RestResponse
    {
        $name = (string) $request->getParam('name');
        $since = (int) ($request->getParam('since') ?? (\time() - 86400));
        $until = (int) ($request->getParam('until') ?? 0);

        $history = $this->metricStore->history($name, $since, $until);
        $latest = $this->metricStore->latest($name);

        if ($history === [] && $latest === null) {
            return new RestResponse([
                'error' => 'Metric not found: ' . $name,
            ], 404);
        }

        return new RestResponse([
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
