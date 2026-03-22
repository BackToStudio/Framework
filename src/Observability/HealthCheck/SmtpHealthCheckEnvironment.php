<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\HealthCheck;

/**
 * Port interface for SMTP health check environment access.
 *
 * Abstracts WordPress functions (get_option, set_transient)
 * so SmtpHealthCheck remains testable without WordPress loaded.
 */
interface SmtpHealthCheckEnvironment
{
    /**
     * Return the cached health check result, or null if expired/missing.
     */
    public function getCachedResult(string $key): ?string;

    /**
     * Cache the health check result for the given TTL (seconds).
     */
    public function setCachedResult(string $key, string $value, int $ttl): void;

    /**
     * Return the site administrator email.
     */
    public function getAdminEmail(): string;

    /**
     * Return the site name for email subjects.
     */
    public function getSiteName(): string;
}
