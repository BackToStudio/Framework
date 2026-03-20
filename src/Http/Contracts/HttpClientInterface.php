<?php

declare(strict_types=1);

namespace BackTo\Framework\Http\Contracts;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Extended HTTP client interface.
 *
 * Extends PSR-18 ClientInterface with convenience methods
 * for common HTTP verbs, avoiding boilerplate request creation.
 *
 * @see ClientInterface for the PSR-18 sendRequest() method
 */
interface HttpClientInterface extends ClientInterface
{
    /**
     * @param array<string, mixed> $options
     */
    public function get(string $url, array $options = []): ResponseInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function post(string $url, array $options = []): ResponseInterface;

    /**
     * @param array<string, mixed> $options
     */
    public function request(string $method, string $url, array $options = []): ResponseInterface;
}
