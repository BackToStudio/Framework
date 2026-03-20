<?php

declare(strict_types=1);

namespace BackTo\Framework\Http\Contracts;

/**
 * Port interface for HTTP client operations.
 *
 * Inspired by PSR-18 and Symfony HttpClient. Provides a simple,
 * framework-agnostic API for sending HTTP requests.
 *
 * Common options (adapter-dependent):
 * - timeout: int — Request timeout in seconds
 * - headers: array<string, string> — HTTP headers
 * - body: string — Request body
 * - blocking: bool — Whether to wait for the response (default: true)
 */
interface HttpClientInterface
{
    /**
     * Send an HTTP request.
     *
     * @param array<string, mixed> $options
     */
    public function request(string $method, string $url, array $options = []): HttpResponseInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function get(string $url, array $options = []): HttpResponseInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function post(string $url, array $options = []): HttpResponseInterface;
}
