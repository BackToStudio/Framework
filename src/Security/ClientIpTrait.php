<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

/**
 * Provides a shared getClientIp() implementation for security rules.
 *
 * Reads the client IP from REMOTE_ADDR by default. Override getClientIp()
 * in your class if you need to support trusted proxy headers.
 */
trait ClientIpTrait
{
    protected function getClientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
