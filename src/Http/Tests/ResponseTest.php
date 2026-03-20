<?php

declare(strict_types=1);

namespace BackTo\Framework\Http\Tests;

use BackTo\Framework\Http\Response;
use BackTo\Framework\Http\StringStream;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

class ResponseTest extends TestCase
{
    public function testImplementsPsr7ResponseInterface(): void
    {
        $this->assertInstanceOf(ResponseInterface::class, new Response());
    }

    public function testDefaultValues(): void
    {
        $response = new Response();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('OK', $response->getReasonPhrase());
        $this->assertSame('1.1', $response->getProtocolVersion());
        $this->assertSame('', (string) $response->getBody());
        $this->assertSame([], $response->getHeaders());
    }

    public function testCustomStatusCode(): void
    {
        $response = new Response(404);
        $this->assertSame(404, $response->getStatusCode());
        $this->assertSame('Not Found', $response->getReasonPhrase());
    }

    public function testCustomReasonPhrase(): void
    {
        $response = new Response(200, [], '', '1.1', 'All Good');
        $this->assertSame('All Good', $response->getReasonPhrase());
    }

    public function testWithStatusReturnsNewInstance(): void
    {
        $response = new Response(200);
        $new = $response->withStatus(404);

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame(404, $new->getStatusCode());
        $this->assertNotSame($response, $new);
    }

    public function testBodyAsString(): void
    {
        $response = new Response(200, [], '{"ok":true}');
        $this->assertSame('{"ok":true}', (string) $response->getBody());
    }

    public function testBodyAsStream(): void
    {
        $stream = new StringStream('stream content');
        $response = new Response(200, [], $stream);
        $this->assertSame($stream, $response->getBody());
    }

    public function testHeaders(): void
    {
        $response = new Response(200, ['Content-Type' => 'application/json']);

        $this->assertTrue($response->hasHeader('Content-Type'));
        $this->assertTrue($response->hasHeader('content-type'));
        $this->assertSame(['application/json'], $response->getHeader('Content-Type'));
        $this->assertSame('application/json', $response->getHeaderLine('Content-Type'));
    }

    public function testGetHeaderCaseInsensitive(): void
    {
        $response = new Response(200, ['X-Custom' => 'value']);
        $this->assertSame(['value'], $response->getHeader('x-custom'));
    }

    public function testGetHeaderMissing(): void
    {
        $response = new Response();
        $this->assertSame([], $response->getHeader('X-Missing'));
        $this->assertSame('', $response->getHeaderLine('X-Missing'));
        $this->assertFalse($response->hasHeader('X-Missing'));
    }

    public function testWithHeaderReturnsNewInstance(): void
    {
        $response = new Response(200, ['X-Old' => 'old']);
        $new = $response->withHeader('X-New', 'new');

        $this->assertFalse($response->hasHeader('X-New'));
        $this->assertTrue($new->hasHeader('X-New'));
        $this->assertTrue($new->hasHeader('X-Old'));
    }

    public function testWithHeaderReplacesExisting(): void
    {
        $response = new Response(200, ['Content-Type' => 'text/html']);
        $new = $response->withHeader('Content-Type', 'application/json');

        $this->assertSame('application/json', $new->getHeaderLine('Content-Type'));
    }

    public function testWithAddedHeader(): void
    {
        $response = new Response(200, ['Accept' => 'text/html']);
        $new = $response->withAddedHeader('Accept', 'application/json');

        $this->assertSame(['text/html', 'application/json'], $new->getHeader('Accept'));
        $this->assertSame('text/html, application/json', $new->getHeaderLine('Accept'));
    }

    public function testWithoutHeader(): void
    {
        $response = new Response(200, ['X-Remove' => 'value', 'X-Keep' => 'keep']);
        $new = $response->withoutHeader('X-Remove');

        $this->assertFalse($new->hasHeader('X-Remove'));
        $this->assertTrue($new->hasHeader('X-Keep'));
    }

    public function testWithProtocolVersion(): void
    {
        $response = new Response();
        $new = $response->withProtocolVersion('2.0');

        $this->assertSame('1.1', $response->getProtocolVersion());
        $this->assertSame('2.0', $new->getProtocolVersion());
    }

    public function testWithBody(): void
    {
        $response = new Response(200, [], 'old');
        $newBody = new StringStream('new');
        $new = $response->withBody($newBody);

        $this->assertSame('old', (string) $response->getBody());
        $this->assertSame('new', (string) $new->getBody());
    }

    public function testGetBodyReturnsStreamInterface(): void
    {
        $response = new Response(200, [], 'content');
        $this->assertInstanceOf(StreamInterface::class, $response->getBody());
    }
}
