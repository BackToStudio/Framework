<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Contracts\ResponseEmitterInterface;
use BackTo\Framework\Bundle\Security\Contracts\CorsManagerInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;

/**
 * Fine-grained CORS header management for headless/SPA WordPress setups.
 *
 * WordPress is permissive by default on CORS. This rule provides explicit
 * control over Access-Control-Allow-Origin, Methods, Headers, Credentials, and Max-Age.
 *
 * Hook priorities:
 * - rest_api_init (5): Early handling to intercept preflight before other REST hooks
 * - rest_pre_serve_request (10): Standard priority for adding CORS headers to responses
 */
class CorsManager implements Hooks, SecurityRuleInterface, CorsManagerInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly RequestContextInterface $requestContext;
    private readonly ResponseEmitterInterface $responseEmitter;

    /** @var string[] */
    private array $allowedOrigins = [];

    /** @var string[] */
    private array $allowedMethods = ['GET', 'POST', 'OPTIONS'];

    /** @var string[] */
    private array $allowedHeaders = ['Content-Type', 'Authorization', 'X-WP-Nonce'];

    private const DEFAULT_MAX_AGE = 86400;

    private bool $allowCredentials = false;

    private int $maxAge = self::DEFAULT_MAX_AGE;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        RequestContextInterface $requestContext,
        ResponseEmitterInterface $responseEmitter,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->requestContext = $requestContext;
        $this->responseEmitter = $responseEmitter;
    }

    public function getName(): string
    {
        return 'cors_manager';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('rest_api_init', [$this, 'handleCors'], 5);
        $this->hookDispatcher->addFilter('rest_pre_serve_request', [$this, 'sendCorsHeaders'], 10, 1);
    }

    public function addAllowedOrigin(string|array $origin): self
    {
        $origins = is_array($origin) ? $origin : [$origin];

        foreach ($origins as $o) {
            if ($o === '*' && $this->allowCredentials) {
                throw new \InvalidArgumentException(
                    'Cannot add wildcard (*) origin when credentials are enabled. '
                    . 'Specify explicit allowed origins instead.'
                );
            }

            if (! in_array($o, $this->allowedOrigins, true)) {
                $this->allowedOrigins[] = $o;
            }
        }

        return $this;
    }

    public function addAllowedMethod(string|array $method): self
    {
        $methods = is_array($method) ? $method : [$method];

        foreach ($methods as $m) {
            $m = strtoupper($m);

            if (! in_array($m, $this->allowedMethods, true)) {
                $this->allowedMethods[] = $m;
            }
        }

        return $this;
    }

    public function addAllowedHeader(string|array $header): self
    {
        $headers = is_array($header) ? $header : [$header];

        foreach ($headers as $h) {
            if (! in_array($h, $this->allowedHeaders, true)) {
                $this->allowedHeaders[] = $h;
            }
        }

        return $this;
    }

    public function setAllowCredentials(bool $allow): self
    {
        if ($allow && in_array('*', $this->allowedOrigins, true)) {
            throw new \InvalidArgumentException(
                'Cannot enable credentials with wildcard (*) origin. '
                . 'Specify explicit allowed origins instead.'
            );
        }

        $this->allowCredentials = $allow;

        return $this;
    }

    public function setMaxAge(int $seconds): self
    {
        $this->maxAge = $seconds;

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function buildHeaders(string $requestOrigin): array
    {
        $headers = [];

        if (! $this->isOriginAllowed($requestOrigin)) {
            return $headers;
        }

        // Reject origins containing CRLF characters to prevent header injection
        if (preg_match('/[\r\n]/', $requestOrigin) === 1) {
            return $headers;
        }

        $headers['Access-Control-Allow-Origin'] = $requestOrigin;
        $headers['Access-Control-Allow-Methods'] = implode(', ', $this->allowedMethods);
        $headers['Access-Control-Allow-Headers'] = implode(', ', $this->allowedHeaders);
        $headers['Access-Control-Max-Age'] = (string) $this->maxAge;

        if ($this->allowCredentials) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        $headers['Vary'] = 'Origin';

        return $headers;
    }

    public function handleCors(): void
    {
        $origin = $this->getRequestOrigin();

        if ($origin === '' || ! $this->isOriginAllowed($origin)) {
            return;
        }

        if ($this->isPreflightRequest()) {
            $this->sendHeaders($origin);
            $this->exitPreflight();
        }
    }

    public function sendCorsHeaders(bool $served): bool
    {
        $origin = $this->getRequestOrigin();

        if ($origin !== '' && $this->isOriginAllowed($origin)) {
            $this->sendHeaders($origin);
        }

        return $served;
    }

    
    public function getAllowedOrigins(): array
    {
        return $this->allowedOrigins;
    }

    
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }

    
    public function getAllowedHeaders(): array
    {
        return $this->allowedHeaders;
    }

    public function isAllowCredentials(): bool
    {
        return $this->allowCredentials;
    }

    public function getMaxAge(): int
    {
        return $this->maxAge;
    }

    private function isOriginAllowed(string $origin): bool
    {
        if ($this->allowedOrigins === []) {
            return false;
        }

        if (in_array('*', $this->allowedOrigins, true)) {
            return true;
        }

        return in_array($origin, $this->allowedOrigins, true);
    }

    protected function sendHeaders(string $origin): void
    {
        if ($this->responseEmitter->headersSent()) {
            return;
        }

        $headers = $this->buildHeaders($origin);

        foreach ($headers as $name => $value) {
            $this->responseEmitter->sendHeader($name . ': ' . $value);
        }
    }

    protected function getRequestOrigin(): string
    {
        return $this->requestContext->server('HTTP_ORIGIN');
    }

    protected function isPreflightRequest(): bool
    {
        return $this->requestContext->getMethod() === 'OPTIONS';
    }

    protected function exitPreflight(): void
    {
        $this->responseEmitter->setStatusCode(200);
        $this->responseEmitter->terminate();
    }
}
