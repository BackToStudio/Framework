<?php

declare(strict_types=1);

namespace BackTo\Framework\RestApi\Contracts;

/**
 * Framework-level REST request abstraction.
 *
 * Decouples route handlers from WP_REST_Request so that domain
 * code never references WordPress types directly.
 */
final class RestRequest
{
    /** @var array<string, mixed> */
    private readonly array $params;

    private readonly string $method;

    /** @var array<string, string> */
    private readonly array $headers;

    private readonly string $body;

    /**
     * @param array<string, mixed>  $params  Merged route + query + body parameters.
     * @param array<string, string> $headers Request headers (lowercased keys).
     */
    public function __construct(
        array $params = [],
        string $method = 'GET',
        array $headers = [],
        string $body = '',
    ) {
        $this->params = $params;
        $this->method = $method;
        $this->headers = $headers;
        $this->body = $body;
    }

    public function getParam(string $key, mixed $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * @return array<string, mixed>
     */
    public function getParams(): array
    {
        return $this->params;
    }

    public function getMethod(): string
    {
        return $this->method;
    }

    public function getHeader(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }

    public function getBody(): string
    {
        return $this->body;
    }
}
