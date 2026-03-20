<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Headers;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\ResponseEmitterInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;

/**
 * Sends security hardening HTTP headers.
 *
 * Hook priorities:
 * - send_headers (10): Standard priority for page requests
 * - rest_api_init (10): Ensures headers are also sent for REST API requests
 *
 * Note: sendSecurityHeaders() guards against double-sending via headersSent().
 */
final class HttpHeadersHardening implements Hooks, SecurityRuleInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly ResponseEmitterInterface $responseEmitter;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        ResponseEmitterInterface $responseEmitter,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->responseEmitter = $responseEmitter;
    }

    public function getName(): string
    {
        return 'http_headers_hardening';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('send_headers', [$this, 'sendSecurityHeaders']);
        $this->hookDispatcher->addAction('rest_api_init', [$this, 'sendSecurityHeaders']);
    }

    public function sendSecurityHeaders(): void
    {
        if ($this->responseEmitter->headersSent()) {
            return;
        }

        $this->responseEmitter->sendHeader('X-Content-Type-Options: nosniff');
        $this->responseEmitter->sendHeader('X-Frame-Options: SAMEORIGIN');
        $this->responseEmitter->sendHeader('Referrer-Policy: strict-origin-when-cross-origin');
        $this->responseEmitter->sendHeader('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        $this->responseEmitter->sendHeader('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
        $this->responseEmitter->removeHeader('X-Powered-By');
    }
}
