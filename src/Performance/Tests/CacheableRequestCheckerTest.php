<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Contracts\UserContextInterface;
use BackTo\Framework\Performance\CacheableRequestChecker;
use PHPUnit\Framework\TestCase;

class CacheableRequestCheckerTest extends TestCase
{
    private RequestContextInterface $requestContext;
    private HookDispatcherInterface $hookDispatcher;
    private UserContextInterface $userContext;

    protected function setUp(): void
    {
        $this->requestContext = $this->createMock(RequestContextInterface::class);
        $this->hookDispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->userContext = $this->createMock(UserContextInterface::class);
    }

    private function createChecker(array $excludedPrefixes = null): CacheableRequestChecker
    {
        if ($excludedPrefixes !== null) {
            return new CacheableRequestChecker(
                $this->requestContext,
                $this->hookDispatcher,
                $this->userContext,
                $excludedPrefixes,
            );
        }

        return new CacheableRequestChecker(
            $this->requestContext,
            $this->hookDispatcher,
            $this->userContext,
        );
    }

    public function testReturnsFalseForAdminRequests(): void
    {
        $this->hookDispatcher->method('isAdmin')->willReturn(true);

        $this->assertFalse($this->createChecker()->isCacheable());
    }

    public function testReturnsFalseForNonGetRequests(): void
    {
        $this->hookDispatcher->method('isAdmin')->willReturn(false);
        $this->requestContext->method('getMethod')->willReturn('POST');

        $this->assertFalse($this->createChecker()->isCacheable());
    }

    public function testReturnsFalseForLoggedInUsers(): void
    {
        $this->hookDispatcher->method('isAdmin')->willReturn(false);
        $this->requestContext->method('getMethod')->willReturn('GET');
        $this->userContext->method('isLoggedIn')->willReturn(true);

        $this->assertFalse($this->createChecker()->isCacheable());
    }

    public function testReturnsFalseWhenQueryParamsPresent(): void
    {
        $this->hookDispatcher->method('isAdmin')->willReturn(false);
        $this->requestContext->method('getMethod')->willReturn('GET');
        $this->userContext->method('isLoggedIn')->willReturn(false);
        $this->requestContext->method('hasQueryParams')->willReturn(true);

        $this->assertFalse($this->createChecker()->isCacheable());
    }

    public function testReturnsFalseForExcludedPrefixes(): void
    {
        $this->hookDispatcher->method('isAdmin')->willReturn(false);
        $this->requestContext->method('getMethod')->willReturn('GET');
        $this->userContext->method('isLoggedIn')->willReturn(false);
        $this->requestContext->method('hasQueryParams')->willReturn(false);
        $this->requestContext->method('getRequestUri')->willReturn('/wp-admin/options.php');

        $this->assertFalse($this->createChecker()->isCacheable());
    }

    public function testReturnsTrueForCacheableRequest(): void
    {
        $this->hookDispatcher->method('isAdmin')->willReturn(false);
        $this->requestContext->method('getMethod')->willReturn('GET');
        $this->userContext->method('isLoggedIn')->willReturn(false);
        $this->requestContext->method('hasQueryParams')->willReturn(false);
        $this->requestContext->method('getRequestUri')->willReturn('/hello-world');

        $this->assertTrue($this->createChecker()->isCacheable());
    }

    public function testCustomExcludedPrefixes(): void
    {
        $this->hookDispatcher->method('isAdmin')->willReturn(false);
        $this->requestContext->method('getMethod')->willReturn('GET');
        $this->userContext->method('isLoggedIn')->willReturn(false);
        $this->requestContext->method('hasQueryParams')->willReturn(false);
        $this->requestContext->method('getRequestUri')->willReturn('/api/data');

        $this->assertFalse($this->createChecker(['/api'])->isCacheable());
    }
}
