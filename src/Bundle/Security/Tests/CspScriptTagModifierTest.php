<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\ResponseEmitterInterface;
use BackTo\Framework\Bundle\Security\Headers\CspDirectiveBuilder;
use BackTo\Framework\Bundle\Security\Headers\CspHeaderSender;
use BackTo\Framework\Bundle\Security\Headers\CspScriptTagModifier;
use PHPUnit\Framework\TestCase;

class CspScriptTagModifierTest extends TestCase
{
    private CspHeaderSender $headerSender;
    private CspScriptTagModifier $modifier;

    protected function setUp(): void
    {
        $responseEmitter = $this->createMock(ResponseEmitterInterface::class);
        $responseEmitter->method('headersSent')->willReturn(false);
        $this->headerSender = new CspHeaderSender($responseEmitter, new CspDirectiveBuilder());
        $this->modifier = new CspScriptTagModifier($this->headerSender);
    }

    public function testAddNonceToScriptsInjectsNonce(): void
    {
        $this->headerSender->generateNonce();
        $nonce = $this->headerSender->getNonce();

        $tag = '<script type="text/javascript" src="app.js"></script>';
        $result = $this->modifier->addNonceToScripts($tag, 'app');

        $this->assertStringContainsString('nonce="' . $nonce . '"', $result);
    }

    public function testAddNonceToScriptsSkipsExistingNonce(): void
    {
        $this->headerSender->generateNonce();

        $tag = '<script nonce="existing" type="text/javascript" src="app.js"></script>';
        $result = $this->modifier->addNonceToScripts($tag, 'app');

        $this->assertSame($tag, $result);
    }

    public function testAddNonceToScriptsSkipsWhenNoNonce(): void
    {
        $tag = '<script type="text/javascript" src="app.js"></script>';
        $result = $this->modifier->addNonceToScripts($tag, 'app');

        $this->assertSame($tag, $result);
    }
}
