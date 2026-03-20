<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Contracts;

/**
 * Resolves the client IP address from the current request.
 *
 * Handles trusted proxy detection and header parsing
 * (CF-Connecting-IP, X-Forwarded-For, X-Real-IP).
 */
interface ClientIpResolverInterface
{
    public function getClientIp(): string;
}
