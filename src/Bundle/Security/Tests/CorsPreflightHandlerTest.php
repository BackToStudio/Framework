<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Contracts\ResponseEmitterInterface;
use BackTo\Framework\Bundle\Security\Headers\CorsHeaderWriter;
use BackTo\Framework\Bundle\Security\Headers\CorsOriginValidator;
use BackTo\Framework\Bundle\Security\Headers\CorsPreflightHandler;
use PHPUnit\Framework\TestCase;

class FakeResponseEmitterForCorsTest implements ResponseEmitterInterface
{
    public bool $terminated = false;
    public int $statusCode = 0;
    /** @var string[] */
    public array $headers = [];

    public function sendHeader(string $header): void { $this->headers[] = $header; }
    public function removeHeader(string $name): void {}
    public function setStatusCode(int $code): void { $this->statusCode = $code; }
    public function headersSent(): bool { return false; }
    public function terminate(): never { $this->terminated = true; throw new \RuntimeException('terminated'); }
}

class CorsPreflightHandlerTest extends TestCase
{
    private RequestContextInterface $requestContext;
    private FakeResponseEmitterForCorsTest $responseEmitter;
    private CorsOriginValidator $originValidator;
    private CorsHeaderWriter $headerWriter;
    private CorsPreflightHandler $handler;

    protected function setUp(): void
    {
        $this->requestContext = $this->createMock(RequestContextInterface::class);
        $this->responseEmitter = new FakeResponseEmitterForCorsTest();
        $this->originValidator = new CorsOriginValidator();
        $this->headerWriter = new CorsHeaderWriter($this->responseEmitter, $this->originValidator);
        $this->handler = new CorsPreflightHandler(
            $this->requestContext,
            $this->responseEmitter,
            $this->originValidator,
            $this->headerWriter,
        );
    }

    public function testHandleCorsPreflightSendsHeadersAndTerminates(): void
    {
        $this->originValidator->addAllowedOrigin('https://example.com');
        $this->requestContext->method('server')->with('HTTP_ORIGIN')->willReturn('https://example.com');
        $this->requestContext->method('getMethod')->willReturn('OPTIONS');

        try {
            $this->handler->handleCors();
        } catch (\RuntimeException) {
            // terminate() throws
        }

        $this->assertSame(200, $this->responseEmitter->statusCode);
        $this->assertTrue($this->responseEmitter->terminated);
    }

    public function testHandleCorsNonPreflightDoesNotTerminate(): void
    {
        $this->originValidator->addAllowedOrigin('https://example.com');
        $this->requestContext->method('server')->with('HTTP_ORIGIN')->willReturn('https://example.com');
        $this->requestContext->method('getMethod')->willReturn('GET');

        $this->handler->handleCors();

        $this->assertFalse($this->responseEmitter->terminated);
    }

    public function testHandleCorsIgnoresDisallowedOrigin(): void
    {
        $this->originValidator->addAllowedOrigin('https://example.com');
        $this->requestContext->method('server')->with('HTTP_ORIGIN')->willReturn('https://evil.com');
        $this->requestContext->method('getMethod')->willReturn('OPTIONS');

        $this->handler->handleCors();

        $this->assertFalse($this->responseEmitter->terminated);
    }

    public function testHandleCorsIgnoresEmptyOrigin(): void
    {
        $this->originValidator->addAllowedOrigin('https://example.com');
        $this->requestContext->method('server')->with('HTTP_ORIGIN')->willReturn('');

        $this->handler->handleCors();

        $this->assertFalse($this->responseEmitter->terminated);
    }
}
