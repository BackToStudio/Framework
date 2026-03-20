<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Headers;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\ResponseEmitterInterface;
use BackTo\Framework\Bundle\Security\Contracts\ContentSecurityPolicyInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;

final class ContentSecurityPolicyManager implements Hooks, SecurityRuleInterface, ContentSecurityPolicyInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly ResponseEmitterInterface $responseEmitter;
    private readonly bool $reportOnly;
    private string $nonce = '';

    /** @var array<string, string[]> */
    private array $directives = [];

    /** @var array<string, string[]> */
    private const DEFAULT_DIRECTIVES = [
        'default-src' => ["'self'"],
        'script-src' => ["'self'"],
        'style-src' => ["'self'", "'unsafe-inline'"],
        'img-src' => ["'self'", 'data:', 'https:'],
        'font-src' => ["'self'", 'data:'],
        'connect-src' => ["'self'"],
        'frame-ancestors' => ["'self'"],
        'base-uri' => ["'self'"],
        'form-action' => ["'self'"],
    ];

    public function __construct(HookDispatcherInterface $hookDispatcher, ResponseEmitterInterface $responseEmitter, bool $reportOnly = false)
    {
        $this->hookDispatcher = $hookDispatcher;
        $this->responseEmitter = $responseEmitter;
        $this->reportOnly = $reportOnly;
        $this->directives = self::DEFAULT_DIRECTIVES;
    }

    public function getName(): string
    {
        return 'content_security_policy';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('send_headers', [$this, 'sendCspHeader']);
        $this->hookDispatcher->addFilter('script_loader_tag', [$this, 'addNonceToScripts'], 10, 2);
    }

    
    public function addDirective(string $directive, string|array $value): self
    {
        $values = is_array($value) ? $value : [$value];

        if (!isset($this->directives[$directive])) {
            $this->directives[$directive] = [];
        }

        foreach ($values as $v) {
            if (!in_array($v, $this->directives[$directive], true)) {
                $this->directives[$directive][] = $v;
            }
        }

        return $this;
    }

    /**
     * @return array<string, string[]>
     */
    public function getDirectives(): array
    {
        return $this->directives;
    }

    public function buildHeaderValue(): string
    {
        $directives = $this->directives;

        $nonce = $this->getNonce();
        if ($nonce !== '') {
            $nonceValue = "'nonce-" . $nonce . "'";

            if (isset($directives['script-src'])) {
                $directives['script-src'][] = $nonceValue;
            }
        }

        $parts = [];

        foreach ($directives as $directive => $values) {
            $parts[] = $directive . ' ' . implode(' ', $values);
        }

        return implode('; ', $parts);
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

        $this->responseEmitter->sendHeader($headerName . ': ' . $this->buildHeaderValue());
    }

    public function addNonceToScripts(string $tag, string $handle): string
    {
        $nonce = $this->getNonce();

        if ($nonce === '') {
            return $tag;
        }

        if (str_contains($tag, 'nonce=')) {
            return $tag;
        }

        return str_replace('<script ', '<script nonce="' . htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8') . '" ', $tag);
    }

    public function isReportOnly(): bool
    {
        return $this->reportOnly;
    }
}
