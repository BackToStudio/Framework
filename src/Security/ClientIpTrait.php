<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\RequestContextInterface;

/**
 * Provides a shared getClientIp() implementation for security rules.
 *
 * Reads trusted proxy headers (CF-Connecting-IP, X-Forwarded-For) when
 * the request comes from a trusted proxy IP. Falls back to REMOTE_ADDR.
 *
 * Configure trusted proxies via the 'backto_trusted_proxies' filter.
 *
 * Classes using this trait MUST provide a getRequestContext() method
 * or have a $requestContext property.
 */
trait ClientIpTrait
{
    /** @var string[]|null */
    private static ?array $trustedProxies = null;

    abstract protected function getRequestContext(): RequestContextInterface;

    protected function getClientIp(): string
    {
        $request = $this->getRequestContext();
        $remoteAddr = $request->getRemoteAddr();

        if (!$this->isFromTrustedProxy($remoteAddr)) {
            return $remoteAddr;
        }

        // CloudFlare
        $cfIp = $request->server('HTTP_CF_CONNECTING_IP');
        if ($cfIp !== '' && filter_var($cfIp, FILTER_VALIDATE_IP) !== false) {
            return $cfIp;
        }

        // Standard proxy header
        $forwardedFor = $request->server('HTTP_X_FORWARDED_FOR');
        if ($forwardedFor !== '') {
            // Take the first (leftmost) IP, which is the original client
            $ips = array_map('trim', explode(',', $forwardedFor));
            $clientIp = $ips[0];
            if (filter_var($clientIp, FILTER_VALIDATE_IP) !== false) {
                return $clientIp;
            }
        }

        $realIp = $request->server('HTTP_X_REAL_IP');
        if ($realIp !== '' && filter_var($realIp, FILTER_VALIDATE_IP) !== false) {
            return $realIp;
        }

        return $remoteAddr;
    }

    private function isFromTrustedProxy(string $remoteAddr): bool
    {
        $trustedProxies = $this->getTrustedProxies();

        if ($trustedProxies === []) {
            return false;
        }

        return in_array($remoteAddr, $trustedProxies, true);
    }


    protected function getTrustedProxies(): array
    {
        if (self::$trustedProxies !== null) {
            return self::$trustedProxies;
        }

        if (function_exists('apply_filters')) {
            self::$trustedProxies = \apply_filters('backto_trusted_proxies', []);
        } else {
            self::$trustedProxies = [];
        }

        return self::$trustedProxies;
    }
}
