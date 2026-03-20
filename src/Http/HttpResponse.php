<?php

declare(strict_types=1);

namespace BackTo\Framework\Http;

use BackTo\Framework\Http\Contracts\HttpResponseInterface;

final class HttpResponse implements HttpResponseInterface
{
    /**
     * @param array<string, string[]> $headers
     */
    public function __construct(
        private readonly int $statusCode,
        private readonly string $body,
        private readonly array $headers = [],
    ) {}

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getHeaders(): array
    {
        return $this->headers;
    }

    public function getHeader(string $name): string
    {
        $normalized = strtolower($name);

        foreach ($this->headers as $key => $values) {
            if (strtolower($key) === $normalized) {
                return implode(', ', $values);
            }
        }

        return '';
    }

    public function isSuccessful(): bool
    {
        return $this->statusCode >= 200 && $this->statusCode < 300;
    }
}
