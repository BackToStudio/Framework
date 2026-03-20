<?php

declare(strict_types=1);

namespace BackTo\Framework\Http\Contracts;

/**
 * Represents an HTTP response.
 *
 * Inspired by PSR-7 ResponseInterface, simplified for framework use.
 */
interface HttpResponseInterface
{
    public function getStatusCode(): int;

    public function getBody(): string;

    /**
     * @return array<string, string[]>
     */
    public function getHeaders(): array;

    public function getHeader(string $name): string;

    public function isSuccessful(): bool;
}
