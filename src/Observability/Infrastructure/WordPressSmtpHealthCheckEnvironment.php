<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\Infrastructure;

use BackTo\Framework\Observability\HealthCheck\SmtpHealthCheckEnvironment;

/**
 * WordPress adapter for SMTP health check environment.
 */
final class WordPressSmtpHealthCheckEnvironment implements SmtpHealthCheckEnvironment
{
    public function getCachedResult(string $key): ?string
    {
        $value = \get_transient($key);

        return \is_string($value) ? $value : null;
    }

    public function setCachedResult(string $key, string $value, int $ttl): void
    {
        \set_transient($key, $value, $ttl);
    }

    public function getAdminEmail(): string
    {
        return (string) \get_option('admin_email', '');
    }

    public function getSiteName(): string
    {
        return (string) \get_option('blogname', 'WordPress');
    }
}
