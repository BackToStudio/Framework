<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

/**
 * Abstraction over HTTP response emission.
 *
 * Replaces direct calls to header(), header_remove(), http_response_code(),
 * and exit in domain code, making it testable.
 */
interface ResponseEmitterInterface
{
    public function sendHeader(string $header): void;

    public function removeHeader(string $name): void;

    public function setStatusCode(int $code): void;

    public function headersSent(): bool;

    /**
     * Terminate the current request.
     *
     * @codeCoverageIgnore
     */
    public function terminate(): never;
}
