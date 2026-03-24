<?php

declare(strict_types=1);

namespace BackTo\Framework\Http\Tests;

use BackTo\Framework\Http\BaseUrlHttpClient;
use BackTo\Framework\Http\Contracts\HttpClientInterface;
use BackTo\Framework\Http\Response;
use PHPUnit\Framework\TestCase;

/**
 * @covers \BackTo\Framework\Http\BaseUrlHttpClient
 */
class BaseUrlHttpClientTest extends TestCase
{
    public function testImplementsHttpClientInterface(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);
        $client = new BaseUrlHttpClient($inner, 'https://api.example.com');

        $this->assertInstanceOf(HttpClientInterface::class, $client);
    }

    public function testGetPrependsBaseUrl(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->once())
            ->method('get')
            ->with('https://api.example.com/v2/users', [])
            ->willReturn(new Response(200));

        $client = new BaseUrlHttpClient($inner, 'https://api.example.com/v2');
        $client->get('/users');
    }

    public function testPostPrependsBaseUrl(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->once())
            ->method('post')
            ->with('https://api.example.com/items', ['body' => 'data'])
            ->willReturn(new Response(201));

        $client = new BaseUrlHttpClient($inner, 'https://api.example.com');
        $client->post('/items', ['body' => 'data']);
    }

    public function testRequestPrependsBaseUrl(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->once())
            ->method('request')
            ->with('DELETE', 'https://api.example.com/users/1', [])
            ->willReturn(new Response(204));

        $client = new BaseUrlHttpClient($inner, 'https://api.example.com');
        $client->request('DELETE', '/users/1');
    }

    public function testAbsoluteUrlIsNotModified(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->once())
            ->method('get')
            ->with('https://other.api.com/data', [])
            ->willReturn(new Response(200));

        $client = new BaseUrlHttpClient($inner, 'https://api.example.com');
        $client->get('https://other.api.com/data');
    }

    public function testHttpAbsoluteUrlIsNotModified(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->once())
            ->method('get')
            ->with('http://insecure.com/data', [])
            ->willReturn(new Response(200));

        $client = new BaseUrlHttpClient($inner, 'https://api.example.com');
        $client->get('http://insecure.com/data');
    }

    public function testTrailingSlashOnBaseUrlIsNormalized(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->once())
            ->method('get')
            ->with('https://api.example.com/users', [])
            ->willReturn(new Response(200));

        $client = new BaseUrlHttpClient($inner, 'https://api.example.com/');
        $client->get('/users');
    }

    public function testRelativePathWithoutLeadingSlash(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);
        $inner->expects($this->once())
            ->method('get')
            ->with('https://api.example.com/users', [])
            ->willReturn(new Response(200));

        $client = new BaseUrlHttpClient($inner, 'https://api.example.com');
        $client->get('users');
    }

    public function testEmptyBaseUrlThrows(): void
    {
        $inner = $this->createMock(HttpClientInterface::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Base URL must not be empty');

        new BaseUrlHttpClient($inner, '');
    }
}
