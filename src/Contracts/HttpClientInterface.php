<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

/**
 * Abstraction over HTTP client operations.
 *
 * Replaces direct calls to wp_remote_get() in domain code.
 */
interface HttpClientInterface
{
    /**
     * Send a non-blocking GET request.
     *
     * @param array<string, mixed> $args Request arguments (timeout, headers, etc.)
     */
    public function get(string $url, array $args = []): void;
}
