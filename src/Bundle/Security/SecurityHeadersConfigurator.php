<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\ResponseEmitterInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;

/**
 * Configurable security headers per environment.
 *
 * Allows fine-tuning of HSTS, Permissions-Policy, X-Frame-Options, and
 * Referrer-Policy headers. Supports environment-specific presets (dev/staging/prod).
 */
class SecurityHeadersConfigurator implements Hooks, SecurityRuleInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly ResponseEmitterInterface $responseEmitter;
    private readonly string $environment;

    /** @var array<string, string> header name => value */
    private array $headers = [];

    /** @var array<string, array<string, string>> */
    private const ENVIRONMENT_PRESETS = [
        'production' => [
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains; preload',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=(), payment=()',
        ],
        'staging' => [
            'Strict-Transport-Security' => 'max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        ],
        'development' => [
            'X-Content-Type-Options' => 'nosniff',
            'X-Frame-Options' => 'SAMEORIGIN',
            'Referrer-Policy' => 'no-referrer-when-downgrade',
        ],
    ];

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        ResponseEmitterInterface $responseEmitter,
        string $environment = 'production',
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->responseEmitter = $responseEmitter;
        $this->environment = $environment;
        $this->headers = self::ENVIRONMENT_PRESETS[$environment] ?? self::ENVIRONMENT_PRESETS['production'];
    }

    public function getName(): string
    {
        return 'security_headers_configurator';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('send_headers', [$this, 'sendHeaders']);
        $this->hookDispatcher->addAction('rest_api_init', [$this, 'sendHeaders']);
    }

    public function setHeader(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    public function removeHeader(string $name): self
    {
        unset($this->headers[$name]);

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getEnvironment(): string
    {
        return $this->environment;
    }

    public function setHstsMaxAge(int $seconds, bool $includeSubDomains = true, bool $preload = false): self
    {
        $value = 'max-age=' . $seconds;

        if ($includeSubDomains) {
            $value .= '; includeSubDomains';
        }

        if ($preload) {
            $value .= '; preload';
        }

        $this->headers['Strict-Transport-Security'] = $value;

        return $this;
    }

    public function setFrameOptions(string $value): self
    {
        $this->headers['X-Frame-Options'] = $value;

        return $this;
    }

    public function setReferrerPolicy(string $policy): self
    {
        $this->headers['Referrer-Policy'] = $policy;

        return $this;
    }

    /**
     * @param array<string, string[]> $permissions Feature => allowed origins (empty = disabled)
     */
    public function setPermissionsPolicy(array $permissions): self
    {
        $parts = [];

        foreach ($permissions as $feature => $origins) {
            if ($origins === []) {
                $parts[] = $feature . '=()';
            } else {
                $parts[] = $feature . '=(' . implode(' ', $origins) . ')';
            }
        }

        $this->headers['Permissions-Policy'] = implode(', ', $parts);

        return $this;
    }

    public function sendHeaders(): void
    {
        if ($this->responseEmitter->headersSent()) {
            return;
        }

        foreach ($this->headers as $name => $value) {
            $this->responseEmitter->sendHeader($name . ': ' . $value);
        }

        $this->responseEmitter->removeHeader('X-Powered-By');
        $this->responseEmitter->removeHeader('Server');
    }

    /**
     * @return array<string, array<string, string>>
     */
    public static function getAvailablePresets(): array
    {
        return self::ENVIRONMENT_PRESETS;
    }
}
