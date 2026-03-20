<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Performance\Tests;

use BackTo\Framework\Contracts\QueryContextInterface;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Contracts\UserContextInterface;
use BackTo\Framework\Bundle\Performance\CacheableRequestChecker;
use PHPUnit\Framework\TestCase;

class CacheableRequestCheckerTest extends TestCase
{
    private RequestContextInterface $requestContext;
    private QueryContextInterface $queryContext;
    private UserContextInterface $userContext;

    protected function setUp(): void
    {
        $this->requestContext = $this->createMock(RequestContextInterface::class);
        $this->queryContext = $this->createMock(QueryContextInterface::class);
        $this->userContext = $this->createMock(UserContextInterface::class);
    }

    private function createChecker(array $excludedPrefixes = null): CacheableRequestChecker
    {
        if ($excludedPrefixes !== null) {
            return new CacheableRequestChecker(
                $this->requestContext,
                $this->queryContext,
                $this->userContext,
                $excludedPrefixes,
            );
        }

        return new CacheableRequestChecker(
            $this->requestContext,
            $this->queryContext,
            $this->userContext,
        );
    }

    public function testReturnsFalseForAdminRequests(): void
    {
        $this->queryContext->method('isAdmin')->willReturn(true);

        $this->assertFalse($this->createChecker()->isCacheable());
    }

    public function testReturnsFalseForNonGetRequests(): void
    {
        $this->queryContext->method('isAdmin')->willReturn(false);
        $this->requestContext->method('getMethod')->willReturn('POST');

        $this->assertFalse($this->createChecker()->isCacheable());
    }

    public function testReturnsFalseForLoggedInUsers(): void
    {
        $this->queryContext->method('isAdmin')->willReturn(false);
        $this->requestContext->method('getMethod')->willReturn('GET');
        $this->userContext->method('isLoggedIn')->willReturn(true);

        $this->assertFalse($this->createChecker()->isCacheable());
    }

    public function testReturnsFalseWhenQueryParamsPresent(): void
    {
        $this->queryContext->method('isAdmin')->willReturn(false);
        $this->requestContext->method('getMethod')->willReturn('GET');
        $this->userContext->method('isLoggedIn')->willReturn(false);
        $this->requestContext->method('hasQueryParams')->willReturn(true);

        $this->assertFalse($this->createChecker()->isCacheable());
    }

    public function testReturnsFalseForExcludedPrefixes(): void
    {
        $this->queryContext->method('isAdmin')->willReturn(false);
        $this->requestContext->method('getMethod')->willReturn('GET');
        $this->userContext->method('isLoggedIn')->willReturn(false);
        $this->requestContext->method('hasQueryParams')->willReturn(false);
        $this->requestContext->method('getRequestUri')->willReturn('/wp-admin/options.php');

        $this->assertFalse($this->createChecker()->isCacheable());
    }

    public function testReturnsTrueForCacheableRequest(): void
    {
        $this->queryContext->method('isAdmin')->willReturn(false);
        $this->requestContext->method('getMethod')->willReturn('GET');
        $this->userContext->method('isLoggedIn')->willReturn(false);
        $this->requestContext->method('hasQueryParams')->willReturn(false);
        $this->requestContext->method('getRequestUri')->willReturn('/hello-world');

        $this->assertTrue($this->createChecker()->isCacheable());
    }

    public function testCustomExcludedPrefixes(): void
    {
        $this->queryContext->method('isAdmin')->willReturn(false);
        $this->requestContext->method('getMethod')->willReturn('GET');
        $this->userContext->method('isLoggedIn')->willReturn(false);
        $this->requestContext->method('hasQueryParams')->willReturn(false);
        $this->requestContext->method('getRequestUri')->willReturn('/api/data');

        $this->assertFalse($this->createChecker(['/api'])->isCacheable());
    }
}
