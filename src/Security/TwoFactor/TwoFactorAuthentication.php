<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\TwoFactor;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Security\TwoFactor\Contracts\BackupCodeManagerInterface;
use BackTo\Framework\Security\TwoFactor\Contracts\TotpProviderInterface;
use BackTo\Framework\Security\TwoFactor\Contracts\TwoFactorRepositoryInterface;

/**
 * Two-Factor Authentication security rule.
 *
 * Intercepts the WordPress login flow to require a TOTP code
 * when 2FA is enabled for a user. Falls back to backup codes.
 *
 * Hook priorities:
 * - authenticate (40): After WordPress core auth (20) and LoginHardening throttle (30)
 */
class TwoFactorAuthentication implements Hooks, SecurityRuleInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly TwoFactorRepositoryInterface $repository;
    private readonly TotpProviderInterface $totpProvider;
    private readonly BackupCodeManagerInterface $backupCodeManager;
    private readonly LoggerInterface $logger;
    private readonly string $issuer;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        TwoFactorRepositoryInterface $repository,
        TotpProviderInterface $totpProvider,
        BackupCodeManagerInterface $backupCodeManager,
        LoggerInterface $logger,
        string $issuer = 'WordPress',
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->repository = $repository;
        $this->totpProvider = $totpProvider;
        $this->backupCodeManager = $backupCodeManager;
        $this->logger = $logger;
        $this->issuer = $issuer;
    }

    public function getName(): string
    {
        return 'two_factor_authentication';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter('authenticate', [$this, 'interceptLogin'], 40, 3);
    }

    /**
     * Intercept login to verify 2FA code when enabled.
     *
     * Priority 40 = after WordPress core auth (20) and LoginHardening throttle (30).
     *
     * @return mixed
     */
    public function interceptLogin(mixed $user, string $username, string $password): mixed
    {
        // Only intercept successful authentications
        if (!$this->isValidUser($user)) {
            return $user;
        }

        $userId = $this->getUserId($user);

        if ($userId === null || !$this->repository->isEnabled($userId)) {
            return $user;
        }

        $code = $this->getTwoFactorCode();

        // No 2FA code submitted — signal the login form to show the 2FA field
        if ($code === null) {
            return $this->createTwoFactorRequiredError($userId);
        }

        // Try TOTP verification first
        $secret = $this->repository->getSecret($userId);

        if ($secret !== null && $this->totpProvider->verifyCode($secret, $code)) {
            $this->logger->info('2FA TOTP verification successful', [
                'user_id' => $userId,
                'username' => $username,
            ]);

            return $user;
        }

        // Try backup code
        if ($this->verifyAndConsumeBackupCode($userId, $code)) {
            $this->logger->info('2FA backup code used', [
                'user_id' => $userId,
                'username' => $username,
            ]);

            return $user;
        }

        $this->logger->warning('2FA verification failed', [
            'user_id' => $userId,
            'username' => $username,
        ]);

        return $this->createInvalidCodeError();
    }

    /**
     * Setup 2FA for a user: generate secret and backup codes.
     *
     * @return array{secret: string, provisioning_uri: string, backup_codes: string[]}
     */
    public function setup(int $userId, string $accountName): array
    {
        $secret = $this->totpProvider->generateSecret();
        $this->repository->setSecret($userId, $secret);

        $backupCodes = $this->backupCodeManager->generate();
        $hashedCodes = array_map(
            fn (string $code): string => $this->backupCodeManager->hash($code),
            $backupCodes
        );
        $this->repository->setBackupCodes($userId, $hashedCodes);

        $provisioningUri = $this->totpProvider->getProvisioningUri(
            $secret,
            $accountName,
            $this->issuer
        );

        return [
            'secret' => $secret,
            'provisioning_uri' => $provisioningUri,
            'backup_codes' => $backupCodes,
        ];
    }

    /**
     * Confirm 2FA setup by verifying the user can produce a valid code.
     */
    public function confirmSetup(int $userId, string $code): bool
    {
        $secret = $this->repository->getSecret($userId);

        if ($secret === null) {
            return false;
        }

        if (!$this->totpProvider->verifyCode($secret, $code)) {
            return false;
        }

        $this->repository->enable($userId);

        $this->logger->info('2FA enabled for user', ['user_id' => $userId]);

        return true;
    }

    /**
     * Disable 2FA for a user and clean up all stored data.
     */
    public function disableForUser(int $userId): void
    {
        $this->repository->disable($userId);
        $this->repository->deleteSecret($userId);
        $this->repository->deleteBackupCodes($userId);

        $this->logger->info('2FA disabled for user', ['user_id' => $userId]);
    }

    /**
     * Regenerate backup codes for a user.
     *
     * @return string[] New plain-text codes.
     */
    public function regenerateBackupCodes(int $userId): array
    {
        $codes = $this->backupCodeManager->generate();
        $hashedCodes = array_map(
            fn (string $code): string => $this->backupCodeManager->hash($code),
            $codes
        );
        $this->repository->setBackupCodes($userId, $hashedCodes);

        $this->logger->info('2FA backup codes regenerated', ['user_id' => $userId]);

        return $codes;
    }

    /**
     * Check if 2FA is enabled for a user.
     */
    public function isEnabledForUser(int $userId): bool
    {
        return $this->repository->isEnabled($userId);
    }

    public function getIssuer(): string
    {
        return $this->issuer;
    }

    private function verifyAndConsumeBackupCode(int $userId, string $code): bool
    {
        $hashedCodes = $this->repository->getBackupCodes($userId);
        $index = $this->backupCodeManager->findMatchingIndex($code, $hashedCodes);

        if ($index === null) {
            return false;
        }

        // Remove the used code
        unset($hashedCodes[$index]);
        $this->repository->setBackupCodes($userId, array_values($hashedCodes));

        $remaining = count($hashedCodes);
        $this->logger->notice('Backup code consumed', [
            'user_id' => $userId,
            'remaining_codes' => $remaining,
        ]);

        return true;
    }

    protected function getTwoFactorCode(): ?string
    {
        $code = $_POST['backto_2fa_code'] ?? null;

        if (!is_string($code) || trim($code) === '') {
            return null;
        }

        // Only allow alphanumeric characters (TOTP codes are digits, backup codes are alphanumeric)
        $sanitized = preg_replace('/[^a-zA-Z0-9]/', '', trim($code));

        if ($sanitized === '' || $sanitized === null) {
            return null;
        }

        return $sanitized;
    }

    protected function isValidUser(mixed $user): bool
    {
        return is_object($user) && isset($user->ID) && !($user instanceof \WP_Error);
    }

    protected function getUserId(mixed $user): ?int
    {
        if (is_object($user) && isset($user->ID)) {
            return (int) $user->ID;
        }

        return null;
    }

    
    protected function createTwoFactorRequiredError(int $userId): mixed
    {
        return new \WP_Error(
            'two_factor_required',
            'Please enter your two-factor authentication code.',
            ['user_id' => $userId]
        );
    }

    
    protected function createInvalidCodeError(): mixed
    {
        return new \WP_Error(
            'two_factor_invalid',
            'Invalid two-factor authentication code.'
        );
    }
}
