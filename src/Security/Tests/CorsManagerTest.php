<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Security\Contracts\CorsManagerInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Security\CorsManager;
use PHPUnit\Framework\TestCase;

class TestableCorsManager extends CorsManager
{
    private string $requestOrigin = '';
    private bool $isPreflight = false;
    public bool $exitCalled = false;

    /** @var array<string, string> */
    public array $sentHeaderValues = [];

    public function setRequestOrigin(string $origin): void
    {
        $this->requestOrigin = $origin;
    }

    public function setPreflight(bool $isPreflight): void
    {
        $this->isPreflight = $isPreflight;
    }

    protected function getRequestOrigin(): string
    {
        return $this->requestOrigin;
    }

    protected function isPreflightRequest(): bool
    {
        return $this->isPreflight;
    }

    protected function headersSent(): bool
    {
        return false;
    }

    protected function sendHeaders(string $origin): void
    {
        $this->sentHeaderValues = $this->buildHeaders($origin);
    }

    protected function exitPreflight(): void
    {
        $this->exitCalled = true;
    }
}

class CorsManagerTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private TestableCorsManager $cors;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->cors = new TestableCorsManager($this->dispatcher);
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

    public function testHandleCorsPreflightExits(): void
    {
        $this->cors->addAllowedOrigin('https://example.com');
        $this->cors->setRequestOrigin('https://example.com');
        $this->cors->setPreflight(true);

        $this->cors->handleCors();

        $this->assertTrue($this->cors->exitCalled);
    }

    public function testHandleCorsNonPreflightDoesNotExit(): void
    {
        $this->cors->addAllowedOrigin('https://example.com');
        $this->cors->setRequestOrigin('https://example.com');
        $this->cors->setPreflight(false);

        $this->cors->handleCors();

        $this->assertFalse($this->cors->exitCalled);
    }

    public function testHandleCorsIgnoresDisallowedOrigin(): void
    {
        $this->cors->addAllowedOrigin('https://example.com');
        $this->cors->setRequestOrigin('https://evil.com');
        $this->cors->setPreflight(true);

        $this->cors->handleCors();

        $this->assertFalse($this->cors->exitCalled);
    }

    public function testHandleCorsIgnoresEmptyOrigin(): void
    {
        $this->cors->addAllowedOrigin('https://example.com');
        $this->cors->setRequestOrigin('');

        $this->cors->handleCors();

        $this->assertFalse($this->cors->exitCalled);
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
