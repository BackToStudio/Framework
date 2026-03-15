<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Security\Contracts\RateLimiterRepositoryInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;

/**
 * Rate limiting for REST API endpoints.
 *
 * Configurable per-route or global limits. Returns standard rate limit
 * headers (X-RateLimit-Limit, X-RateLimit-Remaining, Retry-After).
 */
class RestApiRateLimiter implements Hooks, SecurityRuleInterface
{
    private HookDispatcherInterface $hookDispatcher;
    private RateLimiterRepositoryInterface $repository;

    private int $defaultLimit = 60;
    private int $defaultWindow = 60;

    /** @var array<string, array{limit: int, window: int}> route pattern => config */
    private array $routeLimits = [];

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        RateLimiterRepositoryInterface $repository,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->repository = $repository;
    }

    public function getName(): string
    {
        return 'rest_api_rate_limiter';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter('rest_pre_dispatch', [$this, 'checkRateLimit'], 10, 3);
    }

    public function setDefaultLimit(int $limit): self
    {
        $this->defaultLimit = $limit;

        return $this;
    }

    public function setDefaultWindow(int $seconds): self
    {
        $this->defaultWindow = $seconds;

        return $this;
    }

    /**
     * Set a custom rate limit for a specific route pattern.
     */
    public function setRouteLimit(string $routePattern, int $limit, int $windowSeconds): self
    {
        $this->routeLimits[$routePattern] = [
            'limit' => $limit,
            'window' => $windowSeconds,
        ];

        return $this;
    }

    /**
     * Check rate limit on incoming REST request.
     *
     * @param mixed $result
     * @param mixed $server
     * @param \WP_REST_Request $request
     * @return mixed
     */
    public function checkRateLimit(mixed $result, mixed $server, mixed $request): mixed
    {
        if ($result !== null) {
            return $result;
        }

        $route = $this->getRequestRoute($request);
        $ip = $this->getClientIp();
        $config = $this->getRouteConfig($route);

        $key = $this->buildKey($ip, $route);
        $hits = $this->repository->increment($key, $config['window']);

        $this->sendRateLimitHeaders($config['limit'], $hits, $key);

        if ($hits > $config['limit']) {
            return $this->buildRateLimitResponse($key, $config['limit']);
        }

        return $result;
    }

    /**
     * @return array{limit: int, window: int}
     */
    public function getRouteConfig(string $route): array
    {
        foreach ($this->routeLimits as $pattern => $config) {
            if (str_contains($route, $pattern)) {
                return $config;
            }
        }

        return [
            'limit' => $this->defaultLimit,
            'window' => $this->defaultWindow,
        ];
    }

    /**
     * @return array<string, array{limit: int, window: int}>
     */
    public function getRouteLimits(): array
    {
        return $this->routeLimits;
    }

    public function getDefaultLimit(): int
    {
        return $this->defaultLimit;
    }

    public function getDefaultWindow(): int
    {
        return $this->defaultWindow;
    }

    public function buildKey(string $ip, string $route): string
    {
        return 'rest:' . $ip . ':' . $route;
    }

    protected function getRequestRoute(mixed $request): string
    {
        if (is_object($request) && method_exists($request, 'get_route')) {
            return (string) $request->get_route();
        }

        return '/unknown';
    }

    protected function getClientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    protected function sendRateLimitHeaders(int $limit, int $hits, string $key): void
    {
        if ($this->headersSent()) {
            return;
        }

        $remaining = max(0, $limit - $hits);
        header('X-RateLimit-Limit: ' . $limit);
        header('X-RateLimit-Remaining: ' . $remaining);
    }

    protected function headersSent(): bool
    {
        return headers_sent();
    }

    /**
     * @return \WP_Error
     */
    protected function buildRateLimitResponse(string $key, int $limit): mixed
    {
        $retryAfter = $this->repository->getTtl($key);

        if (! $this->headersSent()) {
            header('Retry-After: ' . $retryAfter);
        }

        return new \WP_Error(
            'rate_limit_exceeded',
            'Rate limit exceeded. Try again in ' . $retryAfter . ' seconds.',
            ['status' => 429]
        );
    }
}
