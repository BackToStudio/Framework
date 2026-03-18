<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Security\Contracts\RateLimiterRepositoryInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Security\RestApiRateLimiter;
use PHPUnit\Framework\TestCase;

class TestableRestApiRateLimiter extends RestApiRateLimiter
{
    private string $clientIp = '1.2.3.4';

    protected function getRequestRoute(mixed $request): string
    {
        if (is_string($request)) {
            return $request;
        }

        return parent::getRequestRoute($request);
    }

    protected function getClientIp(): string
    {
        return $this->clientIp;
    }

    public function setClientIp(string $ip): void
    {
        $this->clientIp = $ip;
    }

    protected function sendRateLimitHeaders(int $limit, int $hits, string $key): void
    {
        // Don't send real headers in tests
    }

    protected function headersSent(): bool
    {
        return true;
    }

    protected function buildRateLimitResponse(string $key, int $limit): mixed
    {
        return ['error' => 'rate_limit_exceeded', 'status' => 429];
    }
}

class RestApiRateLimiterTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private RateLimiterRepositoryInterface $repository;
    private RequestContextInterface $requestContext;
    private TestableRestApiRateLimiter $limiter;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->repository = $this->createMock(RateLimiterRepositoryInterface::class);
        $this->requestContext = $this->createMock(RequestContextInterface::class);
        $this->requestContext->method('getRemoteAddr')->willReturn('127.0.0.1');
        $this->requestContext->method('server')->willReturn('');
        $this->requestContext->method('getMethod')->willReturn('GET');
        $this->limiter = new TestableRestApiRateLimiter($this->dispatcher, $this->repository, $this->requestContext);
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->limiter);
        $this->assertInstanceOf(SecurityRuleInterface::class, $this->limiter);
    }

    public function testGetName(): void
    {
        $this->assertSame('rest_api_rate_limiter', $this->limiter->getName());
    }

    public function testHooksRegistersFilter(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('addFilter')
            ->with('rest_pre_dispatch', $this->anything(), 10, 3);

        $this->limiter->hooks();
    }

    public function testDefaultLimits(): void
    {
        $this->assertSame(60, $this->limiter->getDefaultLimit());
        $this->assertSame(60, $this->limiter->getDefaultWindow());
    }

    public function testSetDefaultLimit(): void
    {
        $this->limiter->setDefaultLimit(100);
        $this->assertSame(100, $this->limiter->getDefaultLimit());
    }

    public function testSetDefaultWindow(): void
    {
        $this->limiter->setDefaultWindow(120);
        $this->assertSame(120, $this->limiter->getDefaultWindow());
    }

    public function testSetRouteLimit(): void
    {
        $this->limiter->setRouteLimit('/wp/v2/posts', 10, 30);

        $limits = $this->limiter->getRouteLimits();
        $this->assertArrayHasKey('/wp/v2/posts', $limits);
        $this->assertSame(10, $limits['/wp/v2/posts']['limit']);
        $this->assertSame(30, $limits['/wp/v2/posts']['window']);
    }

    public function testGetRouteConfigReturnsCustomLimit(): void
    {
        $this->limiter->setRouteLimit('/wp/v2/users', 5, 120);

        $config = $this->limiter->getRouteConfig('/wp/v2/users');
        $this->assertSame(5, $config['limit']);
        $this->assertSame(120, $config['window']);
    }

    public function testGetRouteConfigReturnsDefaultForUnknownRoute(): void
    {
        $config = $this->limiter->getRouteConfig('/wp/v2/unknown');
        $this->assertSame(60, $config['limit']);
        $this->assertSame(60, $config['window']);
    }

    public function testBuildKey(): void
    {
        $key = $this->limiter->buildKey('1.2.3.4', '/wp/v2/posts');
        $this->assertSame('rest:1.2.3.4:/wp/v2/posts', $key);
    }

    public function testCheckRateLimitAllowsWithinLimit(): void
    {
        $this->repository->method('increment')->willReturn(5);

        $result = $this->limiter->checkRateLimit(null, null, '/wp/v2/posts');

        $this->assertNull($result);
    }

    public function testCheckRateLimitBlocksOverLimit(): void
    {
        $this->repository->method('increment')->willReturn(61);

        $result = $this->limiter->checkRateLimit(null, null, '/wp/v2/posts');

        $this->assertIsArray($result);
        $this->assertSame('rate_limit_exceeded', $result['error']);
        $this->assertSame(429, $result['status']);
    }

    public function testCheckRateLimitPassesThroughExistingResult(): void
    {
        $existing = ['data' => 'already_handled'];

        $result = $this->limiter->checkRateLimit($existing, null, '/wp/v2/posts');

        $this->assertSame($existing, $result);
    }

    public function testCheckRateLimitUsesCustomRouteLimit(): void
    {
        $this->limiter->setRouteLimit('/wp/v2/auth', 3, 60);

        $this->repository->method('increment')->willReturn(4);

        $result = $this->limiter->checkRateLimit(null, null, '/wp/v2/auth');

        $this->assertIsArray($result);
        $this->assertSame(429, $result['status']);
    }

    public function testFluentInterface(): void
    {
        $result = $this->limiter
            ->setDefaultLimit(100)
            ->setDefaultWindow(120)
            ->setRouteLimit('/test', 10, 60);

        $this->assertSame($this->limiter, $result);
    }

    public function testZeroLimitBlocksAllRequests(): void
    {
        $this->limiter->setDefaultLimit(0);

        // Even 1 hit exceeds limit of 0
        $this->repository->method('increment')->willReturn(1);

        $result = $this->limiter->checkRateLimit(null, null, '/wp/v2/posts');

        $this->assertIsArray($result);
        $this->assertSame(429, $result['status']);
    }

    public function testZeroWindowRouteLimit(): void
    {
        $this->limiter->setRouteLimit('/wp/v2/fast', 100, 0);

        $config = $this->limiter->getRouteConfig('/wp/v2/fast');
        $this->assertSame(0, $config['window']);
    }

    public function testLargeHitCountOverLimit(): void
    {
        // Simulate extremely high hit count
        $this->repository->method('increment')->willReturn(999999);

        $result = $this->limiter->checkRateLimit(null, null, '/wp/v2/posts');

        $this->assertIsArray($result);
        $this->assertSame(429, $result['status']);
    }
}
