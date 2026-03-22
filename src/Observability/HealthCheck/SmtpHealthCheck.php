<?php

declare(strict_types=1);

namespace BackTo\Framework\Observability\HealthCheck;

use BackTo\Framework\Bundle\Security\Contracts\MailerInterface;
use BackTo\Framework\Contracts\HealthCheckInterface;
use BackTo\Framework\Contracts\HealthCheckResult;

/**
 * Verifies that the email subsystem (SMTP) is operational.
 *
 * Sends a lightweight test email to the site admin address.
 * The check is intentionally kept simple: if wp_mail() returns true,
 * the SMTP transport accepted the message.
 *
 * Rate-limited via a transient to avoid spamming the admin inbox
 * (one real test per hour, cached result in between).
 */
final class SmtpHealthCheck implements HealthCheckInterface
{
    private const CACHE_KEY = 'backto_smtp_health';
    private const CACHE_TTL = 3600;

    private readonly MailerInterface $mailer;
    private readonly SmtpHealthCheckEnvironment $environment;

    public function __construct(MailerInterface $mailer, SmtpHealthCheckEnvironment $environment)
    {
        $this->mailer = $mailer;
        $this->environment = $environment;
    }

    public function getName(): string
    {
        return 'smtp';
    }

    public function check(): HealthCheckResult
    {
        $cached = $this->environment->getCachedResult(self::CACHE_KEY);

        if ($cached !== null) {
            return $cached === 'healthy'
                ? HealthCheckResult::healthy('SMTP operational (cached)')
                : HealthCheckResult::unhealthy('SMTP unreachable (cached)');
        }

        try {
            $adminEmail = $this->environment->getAdminEmail();

            if ($adminEmail === '') {
                return HealthCheckResult::degraded('No admin email configured');
            }

            $start = \hrtime(true);

            $sent = $this->mailer->send(
                $adminEmail,
                '[Health Check] SMTP test — ' . $this->environment->getSiteName(),
                'Automated SMTP health check at ' . \gmdate('Y-m-d H:i:s') . ' UTC. No action required.',
            );

            $elapsed = (\hrtime(true) - $start) / 1_000_000;

            if (!$sent) {
                $this->environment->setCachedResult(self::CACHE_KEY, 'unhealthy', self::CACHE_TTL);

                return HealthCheckResult::unhealthy('wp_mail() returned false — SMTP transport rejected the message');
            }

            $this->environment->setCachedResult(self::CACHE_KEY, 'healthy', self::CACHE_TTL);

            return HealthCheckResult::healthy('SMTP operational', [
                'response_time_ms' => \round($elapsed, 2),
                'recipient' => $adminEmail,
            ]);
        } catch (\Throwable $e) {
            $this->environment->setCachedResult(self::CACHE_KEY, 'unhealthy', self::CACHE_TTL);

            return HealthCheckResult::unhealthy('SMTP error: ' . $e->getMessage());
        }
    }
}
