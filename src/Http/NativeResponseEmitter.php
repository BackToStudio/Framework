<?php

declare(strict_types=1);

namespace BackTo\Framework\Http;

use BackTo\Framework\Contracts\ResponseEmitterInterface;

/**
 * Production implementation using PHP native header functions.
 */
final class NativeResponseEmitter implements ResponseEmitterInterface
{
    public function sendHeader(string $header): void
    {
        header($header);
    }

    public function removeHeader(string $name): void
    {
        header_remove($name);
    }

    public function setStatusCode(int $code): void
    {
        http_response_code($code);
    }

    public function headersSent(): bool
    {
        return headers_sent();
    }

    /**
     * @codeCoverageIgnore
     */
    public function terminate(): never
    {
        exit;
    }
}
