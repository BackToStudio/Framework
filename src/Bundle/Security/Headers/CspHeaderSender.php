<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Headers;

use BackTo\Framework\Contracts\ResponseEmitterInterface;

/**
 * Sends the Content-Security-Policy HTTP header.
 *
 * Single responsibility: generate a nonce, build the header value
 * via the directive builder, and emit it through the response emitter.
 */
final class CspHeaderSender
{
    private readonly ResponseEmitterInterface $responseEmitter;
    private readonly CspDirectiveBuilder $directiveBuilder;
    private readonly bool $reportOnly;
    private string $nonce = '';

    public function __construct(
        ResponseEmitterInterface $responseEmitter,
        CspDirectiveBuilder $directiveBuilder,
        bool $reportOnly = false,
    ) {
        $this->responseEmitter = $responseEmitter;
        $this->directiveBuilder = $directiveBuilder;
        $this->reportOnly = $reportOnly;
    }

    public function generateNonce(): string
    {
        $this->nonce = bin2hex(random_bytes(16));

        return $this->nonce;
    }

    public function getNonce(): string
    {
        return $this->nonce;
    }

    public function sendCspHeader(): void
    {
        if ($this->responseEmitter->headersSent()) {
            return;
        }

        $this->generateNonce();

        $headerName = $this->reportOnly
            ? 'Content-Security-Policy-Report-Only'
            : 'Content-Security-Policy';

        $this->responseEmitter->sendHeader($headerName . ': ' . $this->directiveBuilder->buildHeaderValue($this->nonce));
    }

    public function isReportOnly(): bool
    {
        return $this->reportOnly;
    }
}
