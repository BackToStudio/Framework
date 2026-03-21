<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Contracts\ResponseEmitterInterface;
use BackTo\Framework\Bundle\Security\Contracts\CorsManagerInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Bundle\Security\Headers\CorsManager;
use PHPUnit\Framework\TestCase;

class FakeResponseEmitterForCorsManagerTest implements ResponseEmitterInterface
{
    public int $statusCode = 0;
    public bool $terminated = false;
    /** @var string[] */
    public array $headers = [];

    public function sendHeader(string $header): void { $this->headers[] = $header; }
    public function removeHeader(string $name): void {}
    public function setStatusCode(int $code): void { $this->statusCode = $code; }
    public function headersSent(): bool { return false; }
    public function terminate(): never { $this->terminated = true; throw new \RuntimeException('terminated'); }
}

class CorsManagerTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private RequestContextInterface $requestContext;
    private FakeResponseEmitterForCorsManagerTest $responseEmitter;
    private CorsManager $cors;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->requestContext = $this->createMock(RequestContextInterface::class);
        $this->responseEmitter = new FakeResponseEmitterForCorsManagerTest();
        $this->cors = new CorsManager($this->dispatcher, $this->requestContext, $this->responseEmitter);
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->cors);
        $this->assertInstanceOf(SecurityRuleInterface::class, $this->cors);
        $this->assertInstanceOf(CorsManagerInterface::class, $this->cors);
    }

    public function testGetName(): void
    {
        $this->assertSame('cors_manager', $this->cors->getName());
    }

    public function testHooksRegistersRestApiActions(): void
    {
        $hooks = [];
        $this->dispatcher->expects($this->once())->method('addAction')
            ->willReturnCallback(function (string $hook) use (&$hooks) {
                $hooks[] = $hook;
            });
        $this->dispatcher->expects($this->once())->method('addFilter');

        $this->cors->hooks();
    }

    public function testAddAllowedOrigin(): void
    {
        $this->cors->addAllowedOrigin('https://example.com');
        $this->cors->addAllowedOrigin('https://app.example.com');

        $this->assertSame(['https://example.com', 'https://app.example.com'], $this->cors->getAllowedOrigins());
    }

    public function testAddAllowedOriginDeduplicates(): void
    {
        $this->cors->addAllowedOrigin('https://example.com');
        $this->cors->addAllowedOrigin('https://example.com');

        $this->assertSame(['https://example.com'], $this->cors->getAllowedOrigins());
    }

    public function testAddAllowedOriginAcceptsArray(): void
    {
        $this->cors->addAllowedOrigin(['https://a.com', 'https://b.com']);

        $this->assertSame(['https://a.com', 'https://b.com'], $this->cors->getAllowedOrigins());
    }

    public function testAddAllowedMethod(): void
    {
        $this->cors->addAllowedMethod('PUT');
        $this->cors->addAllowedMethod('delete');

        $this->assertContains('PUT', $this->cors->getAllowedMethods());
        $this->assertContains('DELETE', $this->cors->getAllowedMethods());
    }

    public function testAddAllowedHeader(): void
    {
        $this->cors->addAllowedHeader('X-Custom-Header');

        $this->assertContains('X-Custom-Header', $this->cors->getAllowedHeaders());
    }

    public function testSetAllowCredentials(): void
    {
        $this->assertFalse($this->cors->isAllowCredentials());

        $this->cors->setAllowCredentials(true);

        $this->assertTrue($this->cors->isAllowCredentials());
    }

    public function testSetMaxAge(): void
    {
        $this->cors->setMaxAge(3600);

        $this->assertSame(3600, $this->cors->getMaxAge());
    }

    public function testBuildHeadersForAllowedOrigin(): void
    {
        $this->cors->addAllowedOrigin('https://example.com');

        $headers = $this->cors->buildHeaders('https://example.com');

        $this->assertSame('https://example.com', $headers['Access-Control-Allow-Origin']);
        $this->assertStringContainsString('GET', $headers['Access-Control-Allow-Methods']);
        $this->assertStringContainsString('Content-Type', $headers['Access-Control-Allow-Headers']);
        $this->assertSame('Origin', $headers['Vary']);
    }

    public function testBuildHeadersReturnsEmptyForDisallowedOrigin(): void
    {
        $this->cors->addAllowedOrigin('https://example.com');

        $headers = $this->cors->buildHeaders('https://evil.com');

        $this->assertSame([], $headers);
    }

    public function testBuildHeadersReturnsEmptyWhenNoOriginsConfigured(): void
    {
        $headers = $this->cors->buildHeaders('https://example.com');

        $this->assertSame([], $headers);
    }

    public function testBuildHeadersWithWildcard(): void
    {
        $this->cors->addAllowedOrigin('*');

        $headers = $this->cors->buildHeaders('https://anything.com');

        $this->assertSame('https://anything.com', $headers['Access-Control-Allow-Origin']);
    }

    public function testBuildHeadersWithCredentials(): void
    {
        $this->cors->addAllowedOrigin('https://example.com');
        $this->cors->setAllowCredentials(true);

        $headers = $this->cors->buildHeaders('https://example.com');

        $this->assertSame('true', $headers['Access-Control-Allow-Credentials']);
    }

    public function testBuildHeadersWithoutCredentials(): void
    {
        $this->cors->addAllowedOrigin('https://example.com');

        $headers = $this->cors->buildHeaders('https://example.com');

        $this->assertArrayNotHasKey('Access-Control-Allow-Credentials', $headers);
    }

    public function testHandleCorsPreflightSendsStatusCode(): void
    {
        $this->cors->addAllowedOrigin('https://example.com');
        $this->requestContext->method('server')->with('HTTP_ORIGIN')->willReturn('https://example.com');
        $this->requestContext->method('getMethod')->willReturn('OPTIONS');

        try {
            $this->cors->handleCors();
        } catch (\RuntimeException) {
            // terminate() throws — expected in test environment
        }

        $this->assertSame(200, $this->responseEmitter->statusCode);
    }

    public function testHandleCorsNonPreflightDoesNotSendStatusCode(): void
    {
        $this->cors->addAllowedOrigin('https://example.com');
        $this->requestContext->method('server')->with('HTTP_ORIGIN')->willReturn('https://example.com');
        $this->requestContext->method('getMethod')->willReturn('GET');

        $this->cors->handleCors();

        $this->assertSame(0, $this->responseEmitter->statusCode);
    }

    public function testHandleCorsIgnoresDisallowedOrigin(): void
    {
        $this->cors->addAllowedOrigin('https://example.com');
        $this->requestContext->method('server')->with('HTTP_ORIGIN')->willReturn('https://evil.com');
        $this->requestContext->method('getMethod')->willReturn('OPTIONS');

        $this->cors->handleCors();

        $this->assertSame(0, $this->responseEmitter->statusCode);
    }

    public function testHandleCorsIgnoresEmptyOrigin(): void
    {
        $this->cors->addAllowedOrigin('https://example.com');
        $this->requestContext->method('server')->with('HTTP_ORIGIN')->willReturn('');

        $this->cors->handleCors();

        $this->assertSame(0, $this->responseEmitter->statusCode);
    }

    public function testFluentInterface(): void
    {
        $result = $this->cors
            ->addAllowedOrigin('https://example.com')
            ->addAllowedMethod('PUT')
            ->addAllowedHeader('X-Custom')
            ->setAllowCredentials(true)
            ->setMaxAge(7200);

        $this->assertSame($this->cors, $result);
    }

    public function testWildcardOriginWithCredentialsThrows(): void
    {
        $this->cors->addAllowedOrigin('*');

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/credentials.*wildcard/is');

        $this->cors->setAllowCredentials(true);
    }

    public function testCredentialsWithWildcardOriginThrows(): void
    {
        $this->cors->setAllowCredentials(true);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessageMatches('/wildcard.*origin.*credentials/is');

        $this->cors->addAllowedOrigin('*');
    }

    public function testBuildHeadersRejectsCrlfInOrigin(): void
    {
        $this->cors->addAllowedOrigin('*');

        $headers = $this->cors->buildHeaders("https://evil.com\r\nX-Injected: true");

        $this->assertSame([], $headers);
    }
}
