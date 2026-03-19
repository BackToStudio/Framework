<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\TwoFactor;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Security\TwoFactor\Contracts\BackupCodeManagerInterface;
use BackTo\Framework\Security\TwoFactor\Contracts\TotpProviderInterface;
use BackTo\Framework\Security\TwoFactor\Contracts\TwoFactorRepositoryInterface;

/**
 * Two-Factor Authentication login interceptor.
 *
 * Intercepts the WordPress login flow to require a TOTP code
 * when 2FA is enabled for a user. Falls back to backup codes.
 *
 * For 2FA setup, confirmation, and management, see TwoFactorSetupManager.
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
    private readonly RequestContextInterface $requestContext;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        TwoFactorRepositoryInterface $repository,
        TotpProviderInterface $totpProvider,
        BackupCodeManagerInterface $backupCodeManager,
        LoggerInterface $logger,
        RequestContextInterface $requestContext,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->repository = $repository;
        $this->totpProvider = $totpProvider;
        $this->backupCodeManager = $backupCodeManager;
        $this->logger = $logger;
        $this->requestContext = $requestContext;
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
        $code = $this->requestContext->post('backto_2fa_code');

        if (!is_string($code) || trim($code) === '') {
            return null;
        }

        // Strip non-alphanumeric characters (TOTP codes are digits, backup codes are alphanumeric).
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
