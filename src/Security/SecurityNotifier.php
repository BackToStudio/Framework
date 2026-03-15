<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Security\Contracts\SecurityNotifierInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;

/**
 * Sends email notifications on critical security events.
 *
 * Listens to the SecurityAuditLogger events and sends alerts for:
 * - Login from new country / impossible travel
 * - Self-promotion blocked
 * - File integrity failure
 * - Malware detected in uploads
 * - Critical option changes (admin_email, siteurl, default_role)
 * - New administrator created
 */
class SecurityNotifier implements Hooks, SecurityRuleInterface, SecurityNotifierInterface
{
    private HookDispatcherInterface $hookDispatcher;

    /** @var string[] */
    private array $recipients = [];

    /** @var string[] Events that trigger email notifications */
    private const CRITICAL_EVENTS = [
        'login_anomaly',
        'self_promotion_blocked',
        'file_integrity_failure',
        'malware_detected',
        'critical_option_changed',
        'privileged_role_granted',
        'user_role_changed',
        'login_failed_threshold',
    ];

    private int $failedLoginThreshold = 10;

    /** @var array<string, int> IP => failed count for current request cycle */
    private array $failedLoginCounts = [];

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function getName(): string
    {
        return 'security_notifier';
    }

    public function hooks(): void
    {
        // Listen to audit logger events via custom action
        $this->hookDispatcher->addAction('backto_security_event', [$this, 'onSecurityEvent'], 10, 3);

        // Direct hooks for events we can detect ourselves
        $this->hookDispatcher->addAction('set_user_role', [$this, 'onRoleChange'], 15, 3);
        $this->hookDispatcher->addAction('wp_login_failed', [$this, 'onLoginFailed']);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function notify(string $event, string $severity, array $context): void
    {
        $recipients = $this->resolveRecipients();

        if ($recipients === []) {
            return;
        }

        $subject = $this->buildSubject($event, $severity);
        $body = $this->buildBody($event, $severity, $context);

        foreach ($recipients as $email) {
            $this->sendEmail($email, $subject, $body);
        }
    }

    /**
     * @param string[] $emails
     */
    public function setRecipients(array $emails): self
    {
        $this->recipients = $emails;

        return $this;
    }

    /**
     * @return string[]
     */
    public function getRecipients(): array
    {
        return $this->recipients;
    }

    public function setFailedLoginThreshold(int $threshold): self
    {
        $this->failedLoginThreshold = $threshold;

        return $this;
    }

    /**
     * Handle security events dispatched by other security rules.
     *
     * @param array<string, mixed> $context
     */
    public function onSecurityEvent(string $event, string $severity, array $context): void
    {
        if (! in_array($event, self::CRITICAL_EVENTS, true)) {
            return;
        }

        $this->notify($event, $severity, $context);
    }

    /**
     * @param string[] $oldRoles
     */
    public function onRoleChange(int $userId, string $newRole, array $oldRoles): void
    {
        if ($newRole !== 'administrator') {
            return;
        }

        if (in_array('administrator', $oldRoles, true)) {
            return;
        }

        $this->notify('privileged_role_granted', 'critical', [
            'user_id' => $userId,
            'new_role' => $newRole,
            'old_roles' => $oldRoles,
            'ip' => $this->getClientIp(),
        ]);
    }

    public function onLoginFailed(string $username): void
    {
        $ip = $this->getClientIp();
        $this->failedLoginCounts[$ip] = ($this->failedLoginCounts[$ip] ?? 0) + 1;

        if ($this->failedLoginCounts[$ip] === $this->failedLoginThreshold) {
            $this->notify('login_failed_threshold', 'warning', [
                'ip' => $ip,
                'username' => $username,
                'attempts' => $this->failedLoginCounts[$ip],
            ]);
        }
    }

    /**
     * @return string[]
     */
    public function getCriticalEvents(): array
    {
        return self::CRITICAL_EVENTS;
    }

    public function buildSubject(string $event, string $severity): string
    {
        $siteName = $this->getSiteName();
        $label = strtoupper($severity);
        $eventLabel = str_replace('_', ' ', $event);

        return sprintf('[%s] %s - %s', $label, $siteName, ucfirst($eventLabel));
    }

    /**
     * @param array<string, mixed> $context
     */
    public function buildBody(string $event, string $severity, array $context): string
    {
        $lines = [];
        $lines[] = 'Security Alert: ' . str_replace('_', ' ', $event);
        $lines[] = 'Severity: ' . strtoupper($severity);
        $lines[] = 'Time: ' . gmdate('Y-m-d H:i:s') . ' UTC';
        $lines[] = '';

        foreach ($context as $key => $value) {
            $displayValue = is_array($value) ? implode(', ', array_map('strval', $value)) : (string) $value;
            $lines[] = ucfirst(str_replace('_', ' ', $key)) . ': ' . $displayValue;
        }

        $lines[] = '';
        $lines[] = '---';
        $lines[] = 'This is an automated security notification from BackTo Framework.';

        return implode("\n", $lines);
    }

    /**
     * @return string[]
     */
    protected function resolveRecipients(): array
    {
        if ($this->recipients !== []) {
            return $this->recipients;
        }

        $adminEmail = $this->getAdminEmail();

        return $adminEmail !== '' ? [$adminEmail] : [];
    }

    protected function getAdminEmail(): string
    {
        if (function_exists('get_option')) {
            return (string) get_option('admin_email', '');
        }

        return '';
    }

    protected function getSiteName(): string
    {
        if (function_exists('get_option')) {
            return (string) get_option('blogname', 'WordPress');
        }

        return 'WordPress';
    }

    protected function getClientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    protected function sendEmail(string $to, string $subject, string $body): bool
    {
        if (function_exists('wp_mail')) {
            return wp_mail($to, $subject, $body);
        }

        return false;
    }
}
