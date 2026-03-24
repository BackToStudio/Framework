<?php

declare(strict_types=1);

namespace BackTo\Framework\Http;

use BackTo\Framework\Http\Contracts\HttpClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Decorator that prepends a base URL to relative paths.
 *
 * Useful for API clients that always target the same host:
 *
 *     $client = new BaseUrlHttpClient($inner, 'https://api.example.com/v2');
 *     $client->get('/users');  // → https://api.example.com/v2/users
 */
final class BaseUrlHttpClient implements HttpClientInterface
{
    private readonly HttpClientInterface $inner;
    private readonly string $baseUrl;

    public function __construct(HttpClientInterface $inner, string $baseUrl)
    {
        if ($baseUrl === '') {
            throw new \InvalidArgumentException('Base URL must not be empty.');
        }

        $this->inner = $inner;
        $this->baseUrl = rtrim($baseUrl, '/');
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->inner->sendRequest($request);
    }

    public function get(string $url, array $options = []): ResponseInterface
    {
        return $this->inner->get($this->resolveUrl($url), $options);
    }

    public function post(string $url, array $options = []): ResponseInterface
    {
        return $this->inner->post($this->resolveUrl($url), $options);
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->inner->request($method, $this->resolveUrl($url), $options);
    }

    /**
     * Resolve a URL against the base URL.
     *
     * Absolute URLs (starting with http:// or https://) are returned as-is.
     * Relative paths are prepended with the base URL.
     */
    private function resolveUrl(string $url): string
    {
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
            return $url;
        }

        return $this->baseUrl . '/' . ltrim($url, '/');
    }
}
