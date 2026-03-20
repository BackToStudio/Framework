<?php

declare(strict_types=1);

namespace BackTo\Framework\Http;

use Psr\Http\Message\MessageInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\StreamInterface;

/**
 * Immutable PSR-7 Response implementation.
 */
final class Response implements ResponseInterface
{
    private readonly StreamInterface $body;

    /** @var array<string, string[]> Normalized headers (lowercase key → original values) */
    private readonly array $headers;

    /** @var array<string, string> Lowercase → original case mapping */
    private readonly array $headerNames;

    private const REASON_PHRASES = [
        200 => 'OK', 201 => 'Created', 202 => 'Accepted', 204 => 'No Content',
        301 => 'Moved Permanently', 302 => 'Found', 304 => 'Not Modified',
        400 => 'Bad Request', 401 => 'Unauthorized', 403 => 'Forbidden',
        404 => 'Not Found', 405 => 'Method Not Allowed', 409 => 'Conflict',
        422 => 'Unprocessable Entity', 429 => 'Too Many Requests',
        500 => 'Internal Server Error', 502 => 'Bad Gateway',
        503 => 'Service Unavailable', 504 => 'Gateway Timeout',
    ];

    /**
     * @param array<string, string|string[]> $headers
     */
    public function __construct(
        private readonly int $statusCode = 200,
        array $headers = [],
        string|StreamInterface $body = '',
        private readonly string $protocolVersion = '1.1',
        private readonly string $reasonPhrase = '',
    ) {
        $this->body = $body instanceof StreamInterface ? $body : new StringStream($body);

        $normalizedHeaders = [];
        $headerNames = [];
        foreach ($headers as $name => $value) {
            $normalized = strtolower($name);
            $headerNames[$normalized] = $name;
            $normalizedHeaders[$name] = is_array($value) ? $value : [$value];
        }
        $this->headers = $normalizedHeaders;
        $this->headerNames = $headerNames;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function withStatus(int $code, string $reasonPhrase = ''): ResponseInterface
    {
        return new self($code, $this->headers, $this->body, $this->protocolVersion, $reasonPhrase);
    }

    public function getReasonPhrase(): string
    {
        if ($this->reasonPhrase !== '') {
            return $this->reasonPhrase;
        }

        return self::REASON_PHRASES[$this->statusCode] ?? '';
    }

    public function getProtocolVersion(): string
    {
        return $this->protocolVersion;
    }

    public function withProtocolVersion(string $version): MessageInterface
    {
        return new self($this->statusCode, $this->headers, $this->body, $version, $this->reasonPhrase);
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function hasHeader(string $name): bool
    {
        return isset($this->headerNames[strtolower($name)]);
    }

    public function getHeader(string $name): array
    {
        $normalized = strtolower($name);

        if (!isset($this->headerNames[$normalized])) {
            return [];
        }

        return $this->headers[$this->headerNames[$normalized]];
    }

    public function getHeaderLine(string $name): string
    {
        return implode(', ', $this->getHeader($name));
    }

    public function withHeader(string $name, $value): MessageInterface
    {
        $headers = $this->headers;
        $normalized = strtolower($name);

        // Remove old casing if exists
        if (isset($this->headerNames[$normalized])) {
            unset($headers[$this->headerNames[$normalized]]);
        }

        $headers[$name] = is_array($value) ? $value : [$value];

        return new self($this->statusCode, $headers, $this->body, $this->protocolVersion, $this->reasonPhrase);
    }

    public function withAddedHeader(string $name, $value): MessageInterface
    {
        $headers = $this->headers;
        $normalized = strtolower($name);
        $values = is_array($value) ? $value : [$value];

        if (isset($this->headerNames[$normalized])) {
            $originalName = $this->headerNames[$normalized];
            $headers[$originalName] = array_merge($headers[$originalName], $values);
        } else {
            $headers[$name] = $values;
        }

        return new self($this->statusCode, $headers, $this->body, $this->protocolVersion, $this->reasonPhrase);
    }

    public function withoutHeader(string $name): MessageInterface
    {
        $headers = $this->headers;
        $normalized = strtolower($name);

        if (isset($this->headerNames[$normalized])) {
            unset($headers[$this->headerNames[$normalized]]);
        }

        return new self($this->statusCode, $headers, $this->body, $this->protocolVersion, $this->reasonPhrase);
    }

    public function getBody(): StreamInterface
    {
        return $this->body;
    }

    public function withBody(StreamInterface $body): MessageInterface
    {
        return new self($this->statusCode, $this->headers, $body, $this->protocolVersion, $this->reasonPhrase);
    }
}
