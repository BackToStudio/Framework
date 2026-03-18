<?php

declare(strict_types=1);

namespace BackTo\Framework\Http;

use BackTo\Framework\Contracts\RequestContextInterface;

/**
 * Production implementation backed by PHP superglobals.
 */
final class SuperglobalRequestContext implements RequestContextInterface
{
    public function getMethod(): string
    {
        return $_SERVER['REQUEST_METHOD'] ?? 'GET';
    }

    public function getRequestUri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '/';
    }

    public function getRemoteAddr(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    public function getHost(): string
    {
        return $_SERVER['HTTP_HOST'] ?? 'localhost';
    }

    public function getUserAgent(): string
    {
        return $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    public function server(string $key, string $default = ''): string
    {
        return (string) ($_SERVER[$key] ?? $default);
    }

    public function query(string $key, string $default = ''): string
    {
        return (string) ($_GET[$key] ?? $default);
    }

    public function hasQueryParams(): bool
    {
        return !empty($_GET);
    }

    public function post(string $key, string $default = ''): string
    {
        return (string) ($_POST[$key] ?? $default);
    }

    public function input(string $key, string $default = ''): string
    {
        return (string) ($_REQUEST[$key] ?? $default);
    }

    public function isSecure(): bool
    {
        $https = $_SERVER['HTTPS'] ?? '';

        return $https === 'on' || $https === '1';
    }
}
