<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Security\Contracts\AuditLogRepositoryInterface;
use BackTo\Framework\Bundle\Security\Contracts\AuditLogSeverity;
use BackTo\Framework\Bundle\Security\Contracts\ClientIpResolverInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;

/**
 * Logs security-relevant events to a persistent audit log.
 *
 * Hooks into WordPress actions to capture:
 * - Login successes/failures
 * - User role changes
 * - Critical option updates
 * - Plugin/theme activations and deactivations
 * - User creation/deletion
 *
 * Hook priorities:
 * - wp_login (10): Standard priority
 * - wp_login_failed (10): Standard priority
 * - set_user_role (10): After CapabilityHardening (5), before SecurityNotifier (15)
 * - updated_option (10): Standard priority
 * - activated_plugin / deactivated_plugin (10): Standard priority
 * - switch_theme / user_register / delete_user (10): Standard priority
 */
final class SecurityAuditLogger implements Hooks, SecurityRuleInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly AuditLogRepositoryInterface $repository;
    private readonly ClientIpResolverInterface $ipResolver;

    /** @var string[] Options considered security-critical */
    private const CRITICAL_OPTIONS = [
        'siteurl',
        'home',
        'admin_email',
        'users_can_register',
        'default_role',
        'permalink_structure',
        'blogdescription',
    ];

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        AuditLogRepositoryInterface $repository,
        ClientIpResolverInterface $ipResolver,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->repository = $repository;
        $this->ipResolver = $ipResolver;
    }

    public function getName(): string
    {
        return 'security_audit_logger';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('wp_login', [$this, 'onLoginSuccess'], 10, 2);
        $this->hookDispatcher->addAction('wp_login_failed', [$this, 'onLoginFailed']);
        $this->hookDispatcher->addAction('set_user_role', [$this, 'onUserRoleChanged'], 10, 3);
        $this->hookDispatcher->addAction('updated_option', [$this, 'onOptionUpdated'], 10, 3);
        $this->hookDispatcher->addAction('activated_plugin', [$this, 'onPluginActivated']);
        $this->hookDispatcher->addAction('deactivated_plugin', [$this, 'onPluginDeactivated']);
        $this->hookDispatcher->addAction('switch_theme', [$this, 'onThemeSwitched']);
        $this->hookDispatcher->addAction('user_register', [$this, 'onUserCreated']);
        $this->hookDispatcher->addAction('delete_user', [$this, 'onUserDeleted']);
    }

    public function onLoginSuccess(string $username): void
    {
        $this->repository->store('login_success', AuditLogSeverity::Info->value, [
            'username' => $username,
            'ip' => $this->ipResolver->getClientIp(),
        ]);
    }

    public function onLoginFailed(string $username): void
    {
        $this->repository->store('login_failed', AuditLogSeverity::Warning->value, [
            'username' => $username,
            'ip' => $this->ipResolver->getClientIp(),
        ]);
    }

    
    public function onUserRoleChanged(int $userId, string $newRole, array $oldRoles): void
    {
        $this->repository->store('user_role_changed', AuditLogSeverity::Warning->value, [
            'user_id' => $userId,
            'new_role' => $newRole,
            'old_roles' => $oldRoles,
            'changed_by_ip' => $this->ipResolver->getClientIp(),
        ]);
    }

    public function onOptionUpdated(string $option, mixed $oldValue, mixed $newValue): void
    {
        if (! in_array($option, self::CRITICAL_OPTIONS, true)) {
            return;
        }

        $this->repository->store('critical_option_changed', AuditLogSeverity::Warning->value, [
            'option' => $option,
            'old_value' => $this->sanitizeValue($oldValue),
            'new_value' => $this->sanitizeValue($newValue),
            'ip' => $this->ipResolver->getClientIp(),
        ]);
    }

    public function onPluginActivated(string $plugin): void
    {
        $this->repository->store('plugin_activated', AuditLogSeverity::Info->value, [
            'plugin' => $plugin,
            'ip' => $this->ipResolver->getClientIp(),
        ]);
    }

    public function onPluginDeactivated(string $plugin): void
    {
        $this->repository->store('plugin_deactivated', AuditLogSeverity::Info->value, [
            'plugin' => $plugin,
            'ip' => $this->ipResolver->getClientIp(),
        ]);
    }

    public function onThemeSwitched(string $newTheme): void
    {
        $this->repository->store('theme_switched', AuditLogSeverity::Info->value, [
            'new_theme' => $newTheme,
            'ip' => $this->ipResolver->getClientIp(),
        ]);
    }

    public function onUserCreated(int $userId): void
    {
        $this->repository->store('user_created', AuditLogSeverity::Info->value, [
            'user_id' => $userId,
            'ip' => $this->ipResolver->getClientIp(),
        ]);
    }

    public function onUserDeleted(int $userId): void
    {
        $this->repository->store('user_deleted', AuditLogSeverity::Warning->value, [
            'user_id' => $userId,
            'ip' => $this->ipResolver->getClientIp(),
        ]);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function getAuditLog(int $limit = 100, int $offset = 0): array
    {
        return $this->repository->getEvents([], $limit, $offset);
    }

    public function purgeOldEvents(int $olderThanDays = 90): int
    {
        return $this->repository->purge($olderThanDays);
    }

    private function sanitizeValue(mixed $value): string
    {
        if (is_scalar($value)) {
            return (string) $value;
        }

        return '[complex value]';
    }
}
