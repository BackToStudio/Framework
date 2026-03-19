<?php

declare(strict_types=1);

namespace BackTo\Framework\Http\Tests;

use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Http\SuperglobalRequestContext;
use PHPUnit\Framework\TestCase;

class SuperglobalRequestContextTest extends TestCase
{
    private SuperglobalRequestContext $context;

    /** @var array<string, mixed> */
    private array $originalServer;
    /** @var array<string, mixed> */
    private array $originalGet;
    /** @var array<string, mixed> */
    private array $originalPost;
    /** @var array<string, mixed> */
    private array $originalRequest;

    protected function setUp(): void
    {
        $this->context = new SuperglobalRequestContext();
        $this->originalServer = $_SERVER;
        $this->originalGet = $_GET;
        $this->originalPost = $_POST;
        $this->originalRequest = $_REQUEST;
    }

    protected function tearDown(): void
    {
        $_SERVER = $this->originalServer;
        $_GET = $this->originalGet;
        $_POST = $this->originalPost;
        $_REQUEST = $this->originalRequest;
    }

    public function testImplementsRequestContextInterface(): void
    {
        $this->assertInstanceOf(RequestContextInterface::class, $this->context);
    }

    // ── getMethod ───────────────────────────────────────────

    public function testGetMethodReturnsRequestMethod(): void
    {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $this->assertSame('POST', $this->context->getMethod());
    }

    public function testGetMethodDefaultsToGet(): void
    {
        unset($_SERVER['REQUEST_METHOD']);
        $this->assertSame('GET', $this->context->getMethod());
    }

    // ── getRequestUri ───────────────────────────────────────

    public function testGetRequestUri(): void
    {
        $_SERVER['REQUEST_URI'] = '/foo/bar?baz=1';
        $this->assertSame('/foo/bar?baz=1', $this->context->getRequestUri());
    }

    public function testGetRequestUriDefaultsToSlash(): void
    {
        unset($_SERVER['REQUEST_URI']);
        $this->assertSame('/', $this->context->getRequestUri());
    }

    // ── getRemoteAddr ───────────────────────────────────────

    public function testGetRemoteAddr(): void
    {
        $_SERVER['REMOTE_ADDR'] = '192.168.1.100';
        $this->assertSame('192.168.1.100', $this->context->getRemoteAddr());
    }

    public function testGetRemoteAddrDefaultsToZeros(): void
    {
        unset($_SERVER['REMOTE_ADDR']);
        $this->assertSame('0.0.0.0', $this->context->getRemoteAddr());
    }

    // ── getHost ─────────────────────────────────────────────

    public function testGetHost(): void
    {
        $_SERVER['HTTP_HOST'] = 'example.com';
        $this->assertSame('example.com', $this->context->getHost());
    }

    public function testGetHostDefaultsToLocalhost(): void
    {
        unset($_SERVER['HTTP_HOST']);
        $this->assertSame('localhost', $this->context->getHost());
    }

    // ── getUserAgent ────────────────────────────────────────

    public function testGetUserAgent(): void
    {
        $_SERVER['HTTP_USER_AGENT'] = 'Mozilla/5.0';
        $this->assertSame('Mozilla/5.0', $this->context->getUserAgent());
    }

    public function testGetUserAgentDefaultsToEmpty(): void
    {
        unset($_SERVER['HTTP_USER_AGENT']);
        $this->assertSame('', $this->context->getUserAgent());
    }

    // ── server ──────────────────────────────────────────────

    public function testServerReturnsValue(): void
    {
        $_SERVER['CUSTOM_VAR'] = 'custom_value';
        $this->assertSame('custom_value', $this->context->server('CUSTOM_VAR'));
    }

    public function testServerReturnsDefault(): void
    {
        unset($_SERVER['NONEXISTENT_KEY']);
        $this->assertSame('fallback', $this->context->server('NONEXISTENT_KEY', 'fallback'));
    }

    public function testServerReturnsEmptyStringDefault(): void
    {
        unset($_SERVER['MISSING']);
        $this->assertSame('', $this->context->server('MISSING'));
    }

    // ── query (GET params) ──────────────────────────────────

    public function testQueryReturnsValue(): void
    {
        $_GET['page'] = '2';
        $this->assertSame('2', $this->context->query('page'));
    }

    public function testQueryReturnsDefault(): void
    {
        $_GET = [];
        $this->assertSame('1', $this->context->query('page', '1'));
    }

    // ── hasQueryParams ──────────────────────────────────────

    public function testHasQueryParamsTrue(): void
    {
        $_GET = ['key' => 'value'];
        $this->assertTrue($this->context->hasQueryParams());
    }

    public function testHasQueryParamsFalse(): void
    {
        $_GET = [];
        $this->assertFalse($this->context->hasQueryParams());
    }

    // ── post ────────────────────────────────────────────────

    public function testPostReturnsValue(): void
    {
        $_POST['username'] = 'admin';
        $this->assertSame('admin', $this->context->post('username'));
    }

    public function testPostReturnsDefault(): void
    {
        $_POST = [];
        $this->assertSame('', $this->context->post('username'));
    }

    // ── input ───────────────────────────────────────────────

    public function testInputReturnsRequestValue(): void
    {
        $_REQUEST['token'] = 'abc123';
        $this->assertSame('abc123', $this->context->input('token'));
    }

    public function testInputReturnsDefault(): void
    {
        $_REQUEST = [];
        $this->assertSame('default', $this->context->input('missing', 'default'));
    }

    // ── isSecure ────────────────────────────────────────────

    public function testIsSecureWithHttpsOn(): void
    {
        $_SERVER['HTTPS'] = 'on';
        $this->assertTrue($this->context->isSecure());
    }

    public function testIsSecureWithHttpsOne(): void
    {
        $_SERVER['HTTPS'] = '1';
        $this->assertTrue($this->context->isSecure());
    }

    public function testIsNotSecureWithHttpsOff(): void
    {
        $_SERVER['HTTPS'] = 'off';
        $this->assertFalse($this->context->isSecure());
    }

    public function testIsNotSecureWithHttpsMissing(): void
    {
        unset($_SERVER['HTTPS']);
        $this->assertFalse($this->context->isSecure());
    }

    public function testIsNotSecureWithEmptyString(): void
    {
        $_SERVER['HTTPS'] = '';
        $this->assertFalse($this->context->isSecure());
    }
}
