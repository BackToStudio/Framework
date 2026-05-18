<?php

declare(strict_types=1);

namespace BackTo\Framework\Http\Tests;

use BackTo\Framework\Http\Contracts\HttpClientInterface;
use BackTo\Framework\Http\JsonHttpClient;
use BackTo\Framework\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BackTo\Framework\Http\JsonHttpClient
 */
class JsonHttpClientTest extends TestCase
{
    public function testImplementsHttpClientInterface(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);
        $client = new JsonHttpClient($inner);

        $this->assertInstanceOf(HttpClientInterface::class, $client);
    }

    public function testPostWithJsonOptionEncodesBody(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->once())
            ->method('post')
            ->with(
                'https://api.example.com/users',
                $this->callback(function (array $options): bool {
                    return $options['body'] === '{"name":"Alice","age":30}'
                        && $options['headers']['Content-Type'] === 'application/json'
                        && $options['headers']['Accept'] === 'application/json'
                        && !isset($options['json']);
                })
            )
            ->willReturn(new Response(201));

        $client = new JsonHttpClient($inner);
        $result = $client->post('https://api.example.com/users', [
            'json' => ['name' => 'Alice', 'age' => 30],
        ]);

        $this->assertSame(201, $result->getStatusCode());
    }

    public function testPostWithoutJsonOptionPassesThrough(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->once())
            ->method('post')
            ->with('https://example.com', ['body' => 'raw data'])
            ->willReturn(new Response(200));

        $client = new JsonHttpClient($inner);
        $client->post('https://example.com', ['body' => 'raw data']);
    }

    public function testRequestWithJsonOption(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->once())
            ->method('request')
            ->with(
                'PUT',
                'https://api.example.com/users/1',
                $this->callback(function (array $options): bool {
                    return isset($options['body'])
                        && $options['headers']['Content-Type'] === 'application/json';
                })
            )
            ->willReturn(new Response(200));

        $client = new JsonHttpClient($inner);
        $client->request('PUT', 'https://api.example.com/users/1', [
            'json' => ['name' => 'Bob'],
        ]);
    }

    public function testGetDelegatesToInner(): void
    {
        $response = new Response(200, [], '{"data": true}');

        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->once())
            ->method('get')
            ->with('https://example.com', [])
            ->willReturn($response);

        $client = new JsonHttpClient($inner);
        $result = $client->get('https://example.com');

        $this->assertSame(200, $result->getStatusCode());
    }

    public function testDecodeJsonResponse(): void
    {
        $response = new Response(200, [], '{"name":"Alice","active":true}');

        $decoded = JsonHttpClient::decodeJson($response);

        $this->assertSame(['name' => 'Alice', 'active' => true], $decoded);
    }

    public function testDecodeJsonEmptyBody(): void
    {
        $response = new Response(204);

        $decoded = JsonHttpClient::decodeJson($response);

        $this->assertSame([], $decoded);
    }

    public function testDecodeJsonInvalidJsonThrows(): void
    {
        $response = new Response(200, [], 'not json {{{');

        $this->expectException(\JsonException::class);

        JsonHttpClient::decodeJson($response);
    }

    public function testJsonOptionPreservesExistingHeaders(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->once())
            ->method('post')
            ->with(
                'https://example.com',
                $this->callback(function (array $options): bool {
                    return $options['headers']['Authorization'] === 'Bearer token123'
                        && $options['headers']['Content-Type'] === 'application/json';
                })
            )
            ->willReturn(new Response(200));

        $client = new JsonHttpClient($inner);
        $client->post('https://example.com', [
            'json' => ['data' => true],
            'headers' => ['Authorization' => 'Bearer token123'],
        ]);
    }
}
