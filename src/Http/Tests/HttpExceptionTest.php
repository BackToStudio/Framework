<?php

declare(strict_types=1);

namespace BackTo\Framework\Http\Tests;

use BackTo\Framework\Http\Exception\HttpException;
use BackTo\Framework\Http\Exception\NetworkException;
use BackTo\Framework\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BackTo\Framework\Http\Exception\HttpException
 * @covers \BackTo\Framework\Http\Exception\NetworkException
 */
class HttpExceptionTest extends TestCase
{
    public function testHttpExceptionWithDefaultMessage(): void
    {
        $response = new Response(404);
        $exception = new HttpException($response);

        $this->assertSame(404, $exception->getCode());
        $this->assertSame('HTTP 404: Not Found', $exception->getMessage());
        $this->assertSame($response, $exception->getResponse());
    }

    public function testHttpExceptionWithCustomMessage(): void
    {
        $response = new Response(500);
        $exception = new HttpException($response, 'Server crashed');

        $this->assertSame('Server crashed', $exception->getMessage());
        $this->assertSame(500, $exception->getCode());
    }

    public function testHttpExceptionWithPreviousException(): void
    {
        $previous = new \RuntimeException('inner');
        $response = new Response(502);
        $exception = new HttpException($response, '', $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testHttpExceptionIsRuntimeException(): void
    {
        $exception = new HttpException(new Response(400));
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }

    public function testNetworkExceptionWithReason(): void
    {
        $exception = new NetworkException('https://api.example.com', 'Connection refused');

        $this->assertStringContainsString('https://api.example.com', $exception->getMessage());
        $this->assertStringContainsString('Connection refused', $exception->getMessage());
    }

    public function testNetworkExceptionWithoutReason(): void
    {
        $exception = new NetworkException('https://api.example.com');

        $this->assertSame('Network error for "https://api.example.com"', $exception->getMessage());
    }

    public function testNetworkExceptionWithPreviousException(): void
    {
        $previous = new \RuntimeException('inner');
        $exception = new NetworkException('https://api.example.com', 'timeout', $previous);

        $this->assertSame($previous, $exception->getPrevious());
    }

    public function testNetworkExceptionIsRuntimeException(): void
    {
        $exception = new NetworkException('https://example.com');
        $this->assertInstanceOf(\RuntimeException::class, $exception);
    }
}
