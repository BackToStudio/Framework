<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\ResponseEmitterInterface;
use BackTo\Framework\Bundle\Security\Contracts\ContentSecurityPolicyInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Bundle\Security\Headers\ContentSecurityPolicyManager;
use PHPUnit\Framework\TestCase;

class ContentSecurityPolicyManagerTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private ResponseEmitterInterface $responseEmitter;
    private ContentSecurityPolicyManager $csp;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->responseEmitter = $this->createMock(ResponseEmitterInterface::class);
        $this->responseEmitter->method('headersSent')->willReturn(false);
        $this->csp = new ContentSecurityPolicyManager($this->dispatcher, $this->responseEmitter);
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->csp);
        $this->assertInstanceOf(SecurityRuleInterface::class, $this->csp);
        $this->assertInstanceOf(ContentSecurityPolicyInterface::class, $this->csp);
    }

    public function testGetName(): void
    {
        $this->assertSame('content_security_policy', $this->csp->getName());
    }

    public function testHooksRegistersActionAndFilter(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('addAction')
            ->with('send_headers', $this->anything());

        $this->dispatcher->expects($this->once())
            ->method('addFilter')
            ->with('script_loader_tag', $this->anything(), 10, 2);

        $this->csp->hooks();
    }

    public function testDefaultDirectives(): void
    {
        $directives = $this->csp->getDirectives();

        $this->assertArrayHasKey('default-src', $directives);
        $this->assertArrayHasKey('script-src', $directives);
        $this->assertArrayHasKey('style-src', $directives);
        $this->assertArrayHasKey('img-src', $directives);
        $this->assertArrayHasKey('frame-ancestors', $directives);
        $this->assertArrayHasKey('base-uri', $directives);
        $this->assertArrayHasKey('form-action', $directives);
        $this->assertContains("'self'", $directives['default-src']);
    }

    public function testAddDirectiveString(): void
    {
        $this->csp->addDirective('script-src', 'https://cdn.example.com');

        $directives = $this->csp->getDirectives();
        $this->assertContains('https://cdn.example.com', $directives['script-src']);
    }

    public function testAddDirectiveArray(): void
    {
        $this->csp->addDirective('img-src', ['https://images.example.com', 'https://cdn.example.com']);

        $directives = $this->csp->getDirectives();
        $this->assertContains('https://images.example.com', $directives['img-src']);
        $this->assertContains('https://cdn.example.com', $directives['img-src']);
    }

    public function testAddDirectiveNoDuplicates(): void
    {
        $this->csp->addDirective('default-src', "'self'");

        $count = array_count_values($this->csp->getDirectives()['default-src']);
        $this->assertSame(1, $count["'self'"]);
    }

    public function testAddNewDirective(): void
    {
        $this->csp->addDirective('report-uri', '/csp-report');

        $directives = $this->csp->getDirectives();
        $this->assertArrayHasKey('report-uri', $directives);
        $this->assertContains('/csp-report', $directives['report-uri']);
    }

    public function testBuildHeaderValueWithoutNonce(): void
    {
        $header = $this->csp->buildHeaderValue();

        $this->assertStringContainsString("default-src 'self'", $header);
        $this->assertStringContainsString("script-src 'self'", $header);
        $this->assertStringContainsString('; ', $header);
    }

    public function testBuildHeaderValueWithNonce(): void
    {
        $nonce = $this->csp->generateNonce();
        $header = $this->csp->buildHeaderValue();

        $this->assertStringContainsString("'nonce-" . $nonce . "'", $header);
    }

    public function testGenerateNonce(): void
    {
        $nonce = $this->csp->generateNonce();

        $this->assertSame(32, strlen($nonce)); // 16 bytes = 32 hex chars
        $this->assertTrue(ctype_xdigit($nonce));
    }

    public function testGetNonceEmptyByDefault(): void
    {
        $this->assertSame('', $this->csp->getNonce());
    }

    public function testReportOnlyMode(): void
    {
        $csp = new ContentSecurityPolicyManager($this->dispatcher, $this->responseEmitter, true);
        $this->assertTrue($csp->isReportOnly());
    }

    public function testEnforceMode(): void
    {
        $this->assertFalse($this->csp->isReportOnly());
    }

    public function testAddNonceToScripts(): void
    {
        $this->csp->generateNonce();
        $nonce = $this->csp->getNonce();

        $tag = '<script type="text/javascript" src="app.js"></script>';
        $result = $this->csp->addNonceToScripts($tag, 'app');

        $this->assertStringContainsString('nonce="' . $nonce . '"', $result);
    }

    public function testAddNonceToScriptsSkipsExistingNonce(): void
    {
        $this->csp->generateNonce();

        $tag = '<script nonce="existing" type="text/javascript" src="app.js"></script>';
        $result = $this->csp->addNonceToScripts($tag, 'app');

        $this->assertSame($tag, $result);
    }

    public function testAddNonceToScriptsSkipsWhenNoNonce(): void
    {
        $tag = '<script type="text/javascript" src="app.js"></script>';
        $result = $this->csp->addNonceToScripts($tag, 'app');

        $this->assertSame($tag, $result);
    }
}
