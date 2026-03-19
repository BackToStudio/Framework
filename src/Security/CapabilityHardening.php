<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\UserContextInterface;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Security\Contracts\AuditLogRepositoryInterface;
use BackTo\Framework\Security\Contracts\AuditLogSeverity;
use BackTo\Framework\Security\Contracts\ClientIpResolverInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;

/**
 * Monitors and restricts WordPress capability and role changes.
 *
 * - Blocks self-promotion to administrator role
 * - Monitors role changes on all users
 * - Prevents non-super-admins from granting admin capabilities
 * - Logs all capability-related changes
 */
class CapabilityHardening implements Hooks, SecurityRuleInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly LoggerInterface $logger;
    private readonly AuditLogRepositoryInterface $auditLog;
    private readonly ClientIpResolverInterface $ipResolver;
    private readonly UserContextInterface $userContext;

    /** @var string[] Roles that require elevated verification */
    private const PRIVILEGED_ROLES = [
        'administrator',
    ];

    /** @var string[] Dangerous capabilities that should be monitored */
    private const SENSITIVE_CAPABILITIES = [
        'manage_options',
        'edit_users',
        'delete_users',
        'create_users',
        'promote_users',
        'install_plugins',
        'activate_plugins',
        'delete_plugins',
        'install_themes',
        'edit_themes',
        'switch_themes',
        'update_core',
        'edit_files',
        'unfiltered_html',
        'unfiltered_upload',
    ];

    private bool $blockSelfPromotion = true;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        LoggerInterface $logger,
        AuditLogRepositoryInterface $auditLog,
        ClientIpResolverInterface $ipResolver,
        UserContextInterface $userContext,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->logger = $logger;
        $this->auditLog = $auditLog;
        $this->ipResolver = $ipResolver;
        $this->userContext = $userContext;
    }

    public function getName(): string
    {
        return 'capability_hardening';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('set_user_role', [$this, 'onRoleChange'], 5, 3);
        $this->hookDispatcher->addFilter('user_has_cap', [$this, 'filterCapabilities'], 10, 4);
    }

    public function setBlockSelfPromotion(bool $block): self
    {
        $this->blockSelfPromotion = $block;

        return $this;
    }

    /**
     * Monitor role changes and block self-promotion to admin.
     *
     * @param string[] $oldRoles
     */
    public function onRoleChange(int $userId, string $newRole, array $oldRoles): void
    {
        $isPromotion = $this->isPrivilegedRole($newRole) && ! $this->hasPrivilegedRole($oldRoles);

        if (! $isPromotion) {
            return;
        }

        $currentUserId = $this->getCurrentUserId();

        // Self-promotion detection
        if ($this->blockSelfPromotion && $currentUserId === $userId && $currentUserId !== 0) {
            $this->logger->warning('Blocked self-promotion to privileged role', [
                'user_id' => $userId,
                'attempted_role' => $newRole,
                'ip' => $this->ipResolver->getClientIp(),
            ]);

            $this->auditLog->store('self_promotion_blocked', AuditLogSeverity::Critical->value, [
                'user_id' => $userId,
                'attempted_role' => $newRole,
                'ip' => $this->ipResolver->getClientIp(),
            ]);

            $this->revertRole($userId, $oldRoles);

            return;
        }

        // Log all promotions to privileged roles
        $this->auditLog->store('privileged_role_granted', AuditLogSeverity::Warning->value, [
            'user_id' => $userId,
            'new_role' => $newRole,
            'old_roles' => $oldRoles,
            'granted_by' => $currentUserId,
            'ip' => $this->ipResolver->getClientIp(),
        ]);
    }

    /**
     * Filter capabilities to prevent granting of sensitive caps by non-admins.
     *
     * @param array<string, bool> $allCaps
     * @param string[] $caps
     * @param mixed[] $args
     * @param mixed $user
     * @return array<string, bool>
     */
    public function filterCapabilities(array $allCaps, array $caps, array $args, mixed $user): array
    {
        $requestedCap = $args[0] ?? '';

        if (! is_string($requestedCap)) {
            return $allCaps;
        }

        if ($requestedCap !== 'promote_users') {
            return $allCaps;
        }

        // Only existing admins can promote users
        if (! isset($allCaps['manage_options']) || $allCaps['manage_options'] !== true) {
            $allCaps['promote_users'] = false;
        }

        return $allCaps;
    }

    /**
     * Check if a role is considered privileged.
     */
    public function isPrivilegedRole(string $role): bool
    {
        return in_array($role, self::PRIVILEGED_ROLES, true);
    }

    /**
     * Check if any of the roles are privileged.
     *
     * @param string[] $roles
     */
    public function hasPrivilegedRole(array $roles): bool
    {
        foreach ($roles as $role) {
            if ($this->isPrivilegedRole($role)) {
                return true;
            }
        }

        return false;
    }

    
    public function getSensitiveCapabilities(): array
    {
        return self::SENSITIVE_CAPABILITIES;
    }

    protected function getCurrentUserId(): int
    {
        return $this->userContext->getCurrentUserId();
    }

    /**
     * @param string[] $oldRoles
     */
    protected function revertRole(int $userId, array $oldRoles): void
    {
        $role = $oldRoles[0] ?? 'subscriber';
        $this->userContext->setUserRole($userId, $role);
    }
}
