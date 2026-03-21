<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Headers;

use BackTo\Framework\Contracts\ResponseEmitterInterface;

/**
 * Writes CORS response headers.
 *
 * Single responsibility: build and emit the Access-Control-* headers
 * for a validated origin.
 */
final class CorsHeaderWriter
{
    private readonly ResponseEmitterInterface $responseEmitter;
    private readonly CorsOriginValidator $originValidator;

    /** @var string[] */
    private array $allowedMethods = ['GET', 'POST', 'OPTIONS'];

    /** @var string[] */
    private array $allowedHeaders = ['Content-Type', 'Authorization', 'X-WP-Nonce'];

    private const DEFAULT_MAX_AGE = 86400;

    private int $maxAge = self::DEFAULT_MAX_AGE;

    public function __construct(
        ResponseEmitterInterface $responseEmitter,
        CorsOriginValidator $originValidator,
    ) {
        $this->responseEmitter = $responseEmitter;
        $this->originValidator = $originValidator;
    }

    /**
     * @param string|string[] $method
     */
    public function addAllowedMethod(string|array $method): self
    {
        $methods = is_array($method) ? $method : [$method];

        foreach ($methods as $m) {
            $m = strtoupper($m);

            if (!in_array($m, $this->allowedMethods, true)) {
                $this->allowedMethods[] = $m;
            }
        }

        return $this;
    }

    /**
     * @param string|string[] $header
     */
    public function addAllowedHeader(string|array $header): self
    {
        $headers = is_array($header) ? $header : [$header];

        foreach ($headers as $h) {
            if (!in_array($h, $this->allowedHeaders, true)) {
                $this->allowedHeaders[] = $h;
            }
        }

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

        if (!$this->originValidator->isOriginAllowed($requestOrigin)) {
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

        if ($this->originValidator->isAllowCredentials()) {
            $headers['Access-Control-Allow-Credentials'] = 'true';
        }

        $headers['Vary'] = 'Origin';

        return $headers;
    }

    public function sendHeaders(string $origin): void
    {
        if ($this->responseEmitter->headersSent()) {
            return;
        }

        $headers = $this->buildHeaders($origin);

        foreach ($headers as $name => $value) {
            $this->responseEmitter->sendHeader($name . ': ' . $value);
        }
    }

    /** @return string[] */
    public function getAllowedMethods(): array
    {
        return $this->allowedMethods;
    }

    /** @return string[] */
    public function getAllowedHeaders(): array
    {
        return $this->allowedHeaders;
    }

    public function getMaxAge(): int
    {
        return $this->maxAge;
    }
}
