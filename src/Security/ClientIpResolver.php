<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Security\Contracts\ClientIpResolverInterface;

/**
 * Resolves the real client IP from the current request.
 *
 * Reads trusted proxy headers (CF-Connecting-IP, X-Forwarded-For, X-Real-IP)
 * when the request comes from a trusted proxy IP. Falls back to REMOTE_ADDR.
 *
 * Configure trusted proxies via the 'backto_trusted_proxies' filter.
 */
final class ClientIpResolver implements ClientIpResolverInterface
{
    private readonly RequestContextInterface $requestContext;

    /** @var string[] */
    private readonly array $trustedProxies;

    /**
     * @param string[] $trustedProxies
     */
    public function __construct(RequestContextInterface $requestContext, array $trustedProxies = [])
    {
        $this->requestContext = $requestContext;
        $this->trustedProxies = $trustedProxies;
    }

    public function getClientIp(): string
    {
        $remoteAddr = $this->requestContext->getRemoteAddr();

        if (!$this->isFromTrustedProxy($remoteAddr)) {
            return $remoteAddr;
        }

        // CloudFlare
        $cfIp = $this->requestContext->server('HTTP_CF_CONNECTING_IP');
        if ($cfIp !== '' && filter_var($cfIp, FILTER_VALIDATE_IP) !== false) {
            return $cfIp;
        }

        // Standard proxy header
        $forwardedFor = $this->requestContext->server('HTTP_X_FORWARDED_FOR');
        if ($forwardedFor !== '') {
            $ips = array_map('trim', explode(',', $forwardedFor));
            $clientIp = $ips[0];
            if (filter_var($clientIp, FILTER_VALIDATE_IP) !== false) {
                return $clientIp;
            }
        }

        $realIp = $this->requestContext->server('HTTP_X_REAL_IP');
        if ($realIp !== '' && filter_var($realIp, FILTER_VALIDATE_IP) !== false) {
            return $realIp;
        }

        return $remoteAddr;
    }

    private function isFromTrustedProxy(string $remoteAddr): bool
    {
        if ($this->trustedProxies === []) {
            return false;
        }

        return in_array($remoteAddr, $this->trustedProxies, true);
    }
}
