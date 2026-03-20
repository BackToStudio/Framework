<?php

declare(strict_types=1);

namespace BackTo\Framework\Http\Tests;

use BackTo\Framework\Http\Contracts\HttpResponseInterface;
use BackTo\Framework\Http\HttpResponse;
use PHPUnit\Framework\TestCase;

class HttpResponseTest extends TestCase
{
    public function testImplementsInterface(): void
    {
        $response = new HttpResponse(200, 'OK');
        $this->assertInstanceOf(HttpResponseInterface::class, $response);
    }

    public function testGetStatusCode(): void
    {
        $response = new HttpResponse(404, 'Not Found');
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testGetBody(): void
    {
        $response = new HttpResponse(200, '{"key":"value"}');
        $this->assertSame('{"key":"value"}', $response->getBody());
    }

    public function testGetHeaders(): void
    {
        $headers = ['Content-Type' => ['application/json'], 'X-Custom' => ['foo']];
        $response = new HttpResponse(200, '', $headers);

        $this->assertSame($headers, $response->getHeaders());
    }

    public function testGetHeaderReturnsValue(): void
    {
        $response = new HttpResponse(200, '', ['Content-Type' => ['application/json']]);
        $this->assertSame('application/json', $response->getHeader('Content-Type'));
    }

    public function testGetHeaderIsCaseInsensitive(): void
    {
        $response = new HttpResponse(200, '', ['Content-Type' => ['text/html']]);
        $this->assertSame('text/html', $response->getHeader('content-type'));
    }

    public function testGetHeaderReturnsEmptyStringForMissing(): void
    {
        $response = new HttpResponse(200, '');
        $this->assertSame('', $response->getHeader('X-Missing'));
    }

    public function testGetHeaderJoinsMultipleValues(): void
    {
        $response = new HttpResponse(200, '', ['Accept' => ['text/html', 'application/json']]);
        $this->assertSame('text/html, application/json', $response->getHeader('Accept'));
    }

    public function testIsSuccessfulFor2xxCodes(): void
    {
        $this->assertTrue((new HttpResponse(200, ''))->isSuccessful());
        $this->assertTrue((new HttpResponse(201, ''))->isSuccessful());
        $this->assertTrue((new HttpResponse(204, ''))->isSuccessful());
        $this->assertTrue((new HttpResponse(299, ''))->isSuccessful());
    }

    public function testIsNotSuccessfulForNon2xxCodes(): void
    {
        $this->assertFalse((new HttpResponse(0, ''))->isSuccessful());
        $this->assertFalse((new HttpResponse(301, ''))->isSuccessful());
        $this->assertFalse((new HttpResponse(404, ''))->isSuccessful());
        $this->assertFalse((new HttpResponse(500, ''))->isSuccessful());
    }
}
