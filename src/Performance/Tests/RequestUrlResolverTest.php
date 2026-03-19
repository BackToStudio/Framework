<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Tests;

use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Contracts\SiteContextInterface;
use BackTo\Framework\Performance\RequestUrlResolver;
use PHPUnit\Framework\TestCase;

class RequestUrlResolverTest extends TestCase
{
    private RequestContextInterface $requestContext;
    private SiteContextInterface $siteContext;

    protected function setUp(): void
    {
        $this->requestContext = $this->createMock(RequestContextInterface::class);
        $this->siteContext = $this->createMock(SiteContextInterface::class);
    }

    private function createResolver(): RequestUrlResolver
    {
        return new RequestUrlResolver($this->requestContext, $this->siteContext);
    }

    public function testBuildsUrlFromRequest(): void
    {
        $this->requestContext->method('isSecure')->willReturn(false);
        $this->requestContext->method('getHost')->willReturn('example.com');
        $this->requestContext->method('getRequestUri')->willReturn('/hello-world');
        $this->siteContext->method('getSiteUrl')->willReturn('http://example.com');

        $this->assertSame('http://example.com/hello-world', $this->createResolver()->getCurrentUrl());
    }

    public function testBuildsHttpsUrl(): void
    {
        $this->requestContext->method('isSecure')->willReturn(true);
        $this->requestContext->method('getHost')->willReturn('example.com');
        $this->requestContext->method('getRequestUri')->willReturn('/page');
        $this->siteContext->method('getSiteUrl')->willReturn('https://example.com');

        $this->assertSame('https://example.com/page', $this->createResolver()->getCurrentUrl());
    }

    public function testStripsQueryString(): void
    {
        $this->requestContext->method('isSecure')->willReturn(false);
        $this->requestContext->method('getHost')->willReturn('example.com');
        $this->requestContext->method('getRequestUri')->willReturn('/page?foo=bar');
        $this->siteContext->method('getSiteUrl')->willReturn('http://example.com');

        $this->assertSame('http://example.com/page', $this->createResolver()->getCurrentUrl());
    }

    public function testValidatesHostAgainstSiteUrl(): void
    {
        $this->requestContext->method('isSecure')->willReturn(false);
        $this->requestContext->method('getHost')->willReturn('evil.com');
        $this->requestContext->method('getRequestUri')->willReturn('/page');
        $this->siteContext->method('getSiteUrl')->willReturn('http://example.com');

        $this->assertSame('http://example.com/page', $this->createResolver()->getCurrentUrl());
    }

    public function testUsesRequestHostWhenSiteUrlHasNoHost(): void
    {
        $this->requestContext->method('isSecure')->willReturn(false);
        $this->requestContext->method('getHost')->willReturn('example.com');
        $this->requestContext->method('getRequestUri')->willReturn('/page');
        $this->siteContext->method('getSiteUrl')->willReturn('');

        $this->assertSame('http://example.com/page', $this->createResolver()->getCurrentUrl());
    }
}
