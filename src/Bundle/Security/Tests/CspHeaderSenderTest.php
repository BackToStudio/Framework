<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Tests;

use BackTo\Framework\Contracts\ResponseEmitterInterface;
use BackTo\Framework\Bundle\Security\Headers\CspDirectiveBuilder;
use BackTo\Framework\Bundle\Security\Headers\CspHeaderSender;
use PHPUnit\Framework\TestCase;

class CspHeaderSenderTest extends TestCase
{
    private ResponseEmitterInterface $responseEmitter;
    private CspDirectiveBuilder $directiveBuilder;

    protected function setUp(): void
    {
        $this->responseEmitter = $this->createMock(ResponseEmitterInterface::class);
        $this->directiveBuilder = new CspDirectiveBuilder();
    }

    public function testGenerateNonceReturns32HexChars(): void
    {
        $sender = new CspHeaderSender($this->responseEmitter, $this->directiveBuilder);

        $nonce = $sender->generateNonce();

        $this->assertSame(32, strlen($nonce));
        $this->assertTrue(ctype_xdigit($nonce));
    }

    public function testGetNonceEmptyByDefault(): void
    {
        $sender = new CspHeaderSender($this->responseEmitter, $this->directiveBuilder);

        $this->assertSame('', $sender->getNonce());
    }

    public function testSendCspHeaderEmitsHeader(): void
    {
        $this->responseEmitter->method('headersSent')->willReturn(false);
        $this->responseEmitter->expects($this->once())
            ->method('sendHeader')
            ->with($this->stringStartsWith('Content-Security-Policy: '));

        $sender = new CspHeaderSender($this->responseEmitter, $this->directiveBuilder);
        $sender->sendCspHeader();
    }

    public function testSendCspHeaderSkipsWhenHeadersSent(): void
    {
        $this->responseEmitter->method('headersSent')->willReturn(true);
        $this->responseEmitter->expects($this->never())->method('sendHeader');

        $sender = new CspHeaderSender($this->responseEmitter, $this->directiveBuilder);
        $sender->sendCspHeader();
    }

    public function testReportOnlyModeSendsReportOnlyHeader(): void
    {
        $this->responseEmitter->method('headersSent')->willReturn(false);
        $this->responseEmitter->expects($this->once())
            ->method('sendHeader')
            ->with($this->stringStartsWith('Content-Security-Policy-Report-Only: '));

        $sender = new CspHeaderSender($this->responseEmitter, $this->directiveBuilder, true);
        $sender->sendCspHeader();
    }

    public function testIsReportOnly(): void
    {
        $sender = new CspHeaderSender($this->responseEmitter, $this->directiveBuilder, true);
        $this->assertTrue($sender->isReportOnly());

        $sender2 = new CspHeaderSender($this->responseEmitter, $this->directiveBuilder, false);
        $this->assertFalse($sender2->isReportOnly());
    }
}
