<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\ResponseEmitterInterface;
use BackTo\Framework\Bundle\Security\Headers\CorsHeaderWriter;
use BackTo\Framework\Bundle\Security\Headers\CorsOriginValidator;
use PHPUnit\Framework\TestCase;

class CorsHeaderWriterTest extends TestCase
{
    private ResponseEmitterInterface $responseEmitter;
    private CorsOriginValidator $originValidator;
    private CorsHeaderWriter $writer;

    protected function setUp(): void
    {
        $this->responseEmitter = $this->createMock(ResponseEmitterInterface::class);
        $this->originValidator = new CorsOriginValidator();
        $this->writer = new CorsHeaderWriter($this->responseEmitter, $this->originValidator);
    }

    public function testBuildHeadersForAllowedOrigin(): void
    {
        $this->originValidator->addAllowedOrigin('https://example.com');

        $headers = $this->writer->buildHeaders('https://example.com');

        $this->assertSame('https://example.com', $headers['Access-Control-Allow-Origin']);
        $this->assertStringContainsString('GET', $headers['Access-Control-Allow-Methods']);
        $this->assertStringContainsString('Content-Type', $headers['Access-Control-Allow-Headers']);
        $this->assertSame('Origin', $headers['Vary']);
    }

    public function testBuildHeadersReturnsEmptyForDisallowedOrigin(): void
    {
        $this->originValidator->addAllowedOrigin('https://example.com');

        $this->assertSame([], $this->writer->buildHeaders('https://evil.com'));
    }

    public function testBuildHeadersRejectsCrlfInOrigin(): void
    {
        $this->originValidator->addAllowedOrigin('*');

        $this->assertSame([], $this->writer->buildHeaders("https://evil.com\r\nX-Injected: true"));
    }

    public function testBuildHeadersWithCredentials(): void
    {
        $this->originValidator->addAllowedOrigin('https://example.com');
        $this->originValidator->setAllowCredentials(true);

        $headers = $this->writer->buildHeaders('https://example.com');

        $this->assertSame('true', $headers['Access-Control-Allow-Credentials']);
    }

    public function testBuildHeadersWithoutCredentials(): void
    {
        $this->originValidator->addAllowedOrigin('https://example.com');

        $headers = $this->writer->buildHeaders('https://example.com');

        $this->assertArrayNotHasKey('Access-Control-Allow-Credentials', $headers);
    }

    public function testAddAllowedMethod(): void
    {
        $this->writer->addAllowedMethod('PUT');
        $this->writer->addAllowedMethod('delete');

        $this->assertContains('PUT', $this->writer->getAllowedMethods());
        $this->assertContains('DELETE', $this->writer->getAllowedMethods());
    }

    public function testAddAllowedHeader(): void
    {
        $this->writer->addAllowedHeader('X-Custom-Header');

        $this->assertContains('X-Custom-Header', $this->writer->getAllowedHeaders());
    }

    public function testSetMaxAge(): void
    {
        $this->writer->setMaxAge(3600);

        $this->assertSame(3600, $this->writer->getMaxAge());
    }

    public function testSendHeadersEmitsHeaders(): void
    {
        $this->originValidator->addAllowedOrigin('https://example.com');
        $this->responseEmitter->method('headersSent')->willReturn(false);
        $this->responseEmitter->expects($this->atLeastOnce())->method('sendHeader');

        $this->writer->sendHeaders('https://example.com');
    }

    public function testSendHeadersSkipsWhenHeadersSent(): void
    {
        $this->originValidator->addAllowedOrigin('https://example.com');
        $this->responseEmitter->method('headersSent')->willReturn(true);
        $this->responseEmitter->expects($this->never())->method('sendHeader');

        $this->writer->sendHeaders('https://example.com');
    }
}
