<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

/**
 * Abstraction over the current HTTP request.
 *
 * Replaces direct access to $_SERVER, $_GET, $_POST, $_REQUEST and $_COOKIE
 * in domain and application code, making it testable without superglobals.
 */
interface RequestContextInterface
{
    public function getMethod(): string;

    public function getRequestUri(): string;

    public function getRemoteAddr(): string;

    public function getHost(): string;

    public function getUserAgent(): string;

    /**
     * Read a server variable (equivalent to $_SERVER[$key]).
     */
    public function server(string $key, string $default = ''): string;

    /**
     * Read a query parameter (equivalent to $_GET[$key]).
     */
    public function query(string $key, string $default = ''): string;

    /**
     * Check if any query parameters are present.
     */
    public function hasQueryParams(): bool;

    /**
     * Read a POST parameter (equivalent to $_POST[$key]).
     */
    public function post(string $key, string $default = ''): string;

    /**
     * Read a request parameter from $_REQUEST (GET + POST + COOKIE merged).
     */
    public function input(string $key, string $default = ''): string;

    public function isSecure(): bool;
}
