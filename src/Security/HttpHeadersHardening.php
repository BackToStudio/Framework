<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;

class HttpHeadersHardening implements Hooks, SecurityRuleInterface
{
    private HookDispatcherInterface $hookDispatcher;

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
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
        if (headers_sent()) {
            return;
        }

        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: camera=(), microphone=(), geolocation=()');
        header_remove('X-Powered-By');
    }
}
