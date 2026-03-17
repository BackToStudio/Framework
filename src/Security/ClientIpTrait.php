<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

/**
 * Provides a shared getClientIp() implementation for security rules.
 *
 * Reads trusted proxy headers (CF-Connecting-IP, X-Forwarded-For) when
 * the request comes from a trusted proxy IP. Falls back to REMOTE_ADDR.
 *
 * Configure trusted proxies via the 'backto_trusted_proxies' filter.
 */
trait ClientIpTrait
{
    /** @var string[]|null */
    private static ?array $trustedProxies = null;

    protected function getClientIp(): string
    {
        $remoteAddr = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        if (!$this->isFromTrustedProxy($remoteAddr)) {
            return $remoteAddr;
        }

        // CloudFlare
        if (isset($_SERVER['HTTP_CF_CONNECTING_IP'])) {
            $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
            if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                return $ip;
            }
        }

        // Standard proxy header
        if (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            // Take the first (leftmost) IP, which is the original client
            $ips = array_map('trim', explode(',', $_SERVER['HTTP_X_FORWARDED_FOR']));
            $clientIp = $ips[0];
            if (filter_var($clientIp, FILTER_VALIDATE_IP) !== false) {
                return $clientIp;
            }
        }

        if (isset($_SERVER['HTTP_X_REAL_IP'])) {
            $ip = $_SERVER['HTTP_X_REAL_IP'];
            if (filter_var($ip, FILTER_VALIDATE_IP) !== false) {
                return $ip;
            }
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

    /**
     * @return string[]
     */
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
