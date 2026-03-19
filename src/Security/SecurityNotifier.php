<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Options\Contracts\OptionsRepositoryInterface;
use BackTo\Framework\Security\Contracts\AuditLogSeverity;
use BackTo\Framework\Security\Contracts\ClientIpResolverInterface;
use BackTo\Framework\Security\Contracts\MailerInterface;
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
 *
 * Hook priorities:
 * - backto_security_event (10): Standard priority for custom event hook
 * - set_user_role (15): After CapabilityHardening (5) and SecurityAuditLogger (10)
 * - wp_login_failed (10): Standard priority for login failure counting
 */
final class SecurityNotifier implements Hooks, SecurityRuleInterface, SecurityNotifierInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly MailerInterface $mailer;
    private readonly OptionsRepositoryInterface $options;
    private readonly ClientIpResolverInterface $ipResolver;
    private readonly SecurityAlertFormatter $formatter;

    /** @var string[] */
    private array $recipients = [];

    /** @var string[] Default events that trigger email notifications */
    private const DEFAULT_CRITICAL_EVENTS = [
        'login_anomaly',
        'self_promotion_blocked',
        'file_integrity_failure',
        'malware_detected',
        'critical_option_changed',
        'privileged_role_granted',
        'user_role_changed',
        'login_failed_threshold',
    ];

    /** @var string[] */
    private array $criticalEvents;

    private int $failedLoginThreshold = 10;

    /** @var array<string, int> IP => failed count for current request cycle */
    private array $failedLoginCounts = [];

    /**
     * @param string[] $criticalEvents Override the default list of events that trigger notifications.
     */
    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        MailerInterface $mailer,
        OptionsRepositoryInterface $options,
        ClientIpResolverInterface $ipResolver,
        ?SecurityAlertFormatter $formatter = null,
        array $criticalEvents = [],
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->mailer = $mailer;
        $this->options = $options;
        $this->ipResolver = $ipResolver;
        $this->formatter = $formatter ?? new SecurityAlertFormatter($options);
        $this->criticalEvents = $criticalEvents !== [] ? $criticalEvents : self::DEFAULT_CRITICAL_EVENTS;
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

    
    public function setRecipients(array $emails): self
    {
        $this->recipients = $emails;

        return $this;
    }

    
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
        if (! in_array($event, $this->criticalEvents, true)) {
            return;
        }

        $this->notify($event, $severity, $context);
    }

    
    public function onRoleChange(int $userId, string $newRole, array $oldRoles): void
    {
        if ($newRole !== 'administrator') {
            return;
        }

        if (in_array('administrator', $oldRoles, true)) {
            return;
        }

        $this->notify('privileged_role_granted', AuditLogSeverity::Critical->value, [
            'user_id' => $userId,
            'new_role' => $newRole,
            'old_roles' => $oldRoles,
            'ip' => $this->ipResolver->getClientIp(),
        ]);
    }

    public function onLoginFailed(string $username): void
    {
        $ip = $this->ipResolver->getClientIp();
        $this->failedLoginCounts[$ip] = ($this->failedLoginCounts[$ip] ?? 0) + 1;

        if ($this->failedLoginCounts[$ip] === $this->failedLoginThreshold) {
            $this->notify('login_failed_threshold', AuditLogSeverity::Warning->value, [
                'ip' => $ip,
                'username' => $username,
                'attempts' => $this->failedLoginCounts[$ip],
            ]);
        }
    }

    
    public function addCriticalEvent(string $event): self
    {
        if (! in_array($event, $this->criticalEvents, true)) {
            $this->criticalEvents[] = $event;
        }

        return $this;
    }

    /**
     * @return string[]
     */
    public function getCriticalEvents(): array
    {
        return $this->criticalEvents;
    }

    public function buildSubject(string $event, string $severity): string
    {
        return $this->formatter->buildSubject($event, $severity);
    }

    /**
     * @param array<string, mixed> $context
     */
    public function buildBody(string $event, string $severity, array $context): string
    {
        return $this->formatter->buildBody($event, $severity, $context);
    }

    
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
        return (string) $this->options->get('admin_email', '');
    }

    protected function sendEmail(string $to, string $subject, string $body): bool
    {
        return $this->mailer->send($to, $subject, $body);
    }
}
