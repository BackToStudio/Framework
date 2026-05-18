<?php

declare(strict_types=1);

namespace BackTo\Framework\Http\Tests;

use BackTo\Framework\Http\Contracts\HttpClientInterface;
use BackTo\Framework\Http\Response;
use BackTo\Framework\Http\RetryableHttpClient;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BackTo\Framework\Http\RetryableHttpClient
 */
class RetryableHttpClientTest extends TestCase
{
    private function noSleep(): callable
    {
        return static function (int $us): void {};
    }

    public function testImplementsHttpClientInterface(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);
        $client = new RetryableHttpClient($inner);

        $this->assertInstanceOf(HttpClientInterface::class, $client);
    }

    public function testSuccessfulRequestIsNotRetried(): void
    {
        $response = new Response(200, [], 'ok');

        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $client = new RetryableHttpClient($inner, maxRetries: 3, delayMs: 0, sleepFn: $this->noSleep());
        $result = $client->get('https://example.com');

        $this->assertSame(200, $result->getStatusCode());
    }

    public function testRetryOn503ForGetRequest(): void
    {
        $failResponse = new Response(503);
        $okResponse = new Response(200, [], 'ok');

        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($failResponse, $okResponse);

        $client = new RetryableHttpClient($inner, maxRetries: 3, delayMs: 0, sleepFn: $this->noSleep());
        $result = $client->get('https://example.com');

        $this->assertSame(200, $result->getStatusCode());
    }

    public function testDoesNotRetryPostByDefault(): void
    {
        $failResponse = new Response(503);

        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->once())
            ->method('request')
            ->willReturn($failResponse);

        $client = new RetryableHttpClient($inner, maxRetries: 3, delayMs: 0, sleepFn: $this->noSleep());
        $result = $client->post('https://example.com');

        $this->assertSame(503, $result->getStatusCode());
    }

    public function testRetriesPostWhenExplicitlyAllowed(): void
    {
        $failResponse = new Response(503);
        $okResponse = new Response(200);

        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($failResponse, $okResponse);

        $client = new RetryableHttpClient(
            $inner,
            maxRetries: 3,
            delayMs: 0,
            retryableMethods: ['GET', 'POST'],
            sleepFn: $this->noSleep(),
        );
        $result = $client->post('https://example.com');

        $this->assertSame(200, $result->getStatusCode());
    }

    public function testRetryOn429(): void
    {
        $rateLimited = new Response(429);
        $okResponse = new Response(200);

        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($rateLimited, $okResponse);

        $client = new RetryableHttpClient($inner, maxRetries: 3, delayMs: 0, sleepFn: $this->noSleep());
        $result = $client->get('https://example.com');

        $this->assertSame(200, $result->getStatusCode());
    }

    public function testReturnsLastResponseAfterMaxRetries(): void
    {
        $failResponse = new Response(502);

        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->exactly(3))
            ->method('request')
            ->willReturn($failResponse);

        $client = new RetryableHttpClient($inner, maxRetries: 2, delayMs: 0, sleepFn: $this->noSleep());
        $result = $client->request('GET', 'https://example.com');

        $this->assertSame(502, $result->getStatusCode());
    }

    public function testRetryOnExceptionForGetRequest(): void
    {
        $okResponse = new Response(200);

        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->exactly(2))
            ->method('request')
            ->willReturnCallback(function () use ($okResponse) {
                static $calls = 0;
                $calls++;
                if ($calls === 1) {
                    throw new \RuntimeException('Connection timeout');
                }
                return $okResponse;
            });

        $client = new RetryableHttpClient($inner, maxRetries: 3, delayMs: 0, sleepFn: $this->noSleep());
        $result = $client->get('https://example.com');

        $this->assertSame(200, $result->getStatusCode());
    }

    public function testDoesNotRetryExceptionForPostByDefault(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->once())
            ->method('request')
            ->willThrowException(new \RuntimeException('Connection refused'));

        $client = new RetryableHttpClient($inner, maxRetries: 3, delayMs: 0, sleepFn: $this->noSleep());

        $this->expectException(\RuntimeException::class);
        $client->post('https://example.com');
    }

    public function testThrowsExceptionAfterMaxRetriesExhausted(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->exactly(3))
            ->method('request')
            ->willThrowException(new \RuntimeException('Connection refused'));

        $client = new RetryableHttpClient($inner, maxRetries: 2, delayMs: 0, sleepFn: $this->noSleep());

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Connection refused');

        $client->get('https://example.com');
    }

    public function testNon5xxIsNotRetried(): void
    {
        $response = new Response(404);

        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $client = new RetryableHttpClient($inner, maxRetries: 3, delayMs: 0, sleepFn: $this->noSleep());
        $result = $client->get('https://example.com');

        $this->assertSame(404, $result->getStatusCode());
    }

    public function testCustomRetryableStatusCodes(): void
    {
        $fail = new Response(408);
        $ok = new Response(200);

        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($fail, $ok);

        $client = new RetryableHttpClient($inner, maxRetries: 3, delayMs: 0, retryableStatusCodes: [408], sleepFn: $this->noSleep());
        $result = $client->get('https://example.com');

        $this->assertSame(200, $result->getStatusCode());
    }

    public function testZeroMaxRetriesDoesNotRetry(): void
    {
        $response = new Response(503);

        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->once())
            ->method('request')
            ->willReturn($response);

        $client = new RetryableHttpClient($inner, maxRetries: 0, delayMs: 0, sleepFn: $this->noSleep());
        $result = $client->get('https://example.com');

        $this->assertSame(503, $result->getStatusCode());
    }

    public function testNegativeMaxRetriesThrows(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Max retries must be between 0 and 10');

        new RetryableHttpClient($inner, maxRetries: -1);
    }

    public function testExcessiveMaxRetriesThrows(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Max retries must be between 0 and 10');

        new RetryableHttpClient($inner, maxRetries: 20);
    }

    public function testNegativeDelayThrows(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Delay must be zero or positive');

        new RetryableHttpClient($inner, delayMs: -100);
    }

    public function testSleepFunctionIsCalled(): void
    {
        $sleepCalls = [];
        $sleepFn = function (int $us) use (&$sleepCalls): void {
            $sleepCalls[] = $us;
        };

        $failResponse = new Response(503);
        $okResponse = new Response(200);

        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->exactly(2))
            ->method('request')
            ->willReturnOnConsecutiveCalls($failResponse, $okResponse);

        $client = new RetryableHttpClient($inner, maxRetries: 3, delayMs: 100, sleepFn: $sleepFn);
        $client->get('https://example.com');

        $this->assertCount(1, $sleepCalls);
        // delayMs=100, attempt=0: min(100 * (1 << 0), 60000) * 1000 = 100 * 1000 = 100000µs
        $this->assertSame(100000, $sleepCalls[0]);
    }
}
