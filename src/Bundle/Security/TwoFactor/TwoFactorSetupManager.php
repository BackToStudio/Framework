<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\TwoFactor;

use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Bundle\Security\TwoFactor\Contracts\BackupCodeManagerInterface;
use BackTo\Framework\Bundle\Security\TwoFactor\Contracts\TotpProviderInterface;
use BackTo\Framework\Bundle\Security\TwoFactor\Contracts\TwoFactorRepositoryInterface;

/**
 * Manages 2FA lifecycle: setup, confirmation, disabling, and backup code regeneration.
 *
 * Extracted from TwoFactorAuthentication to separate configuration concerns
 * from the authentication flow (SRP).
 */
final class TwoFactorSetupManager
{
    private readonly TwoFactorRepositoryInterface $repository;
    private readonly TotpProviderInterface $totpProvider;
    private readonly BackupCodeManagerInterface $backupCodeManager;
    private readonly LoggerInterface $logger;
    private readonly string $issuer;

    public function __construct(
        TwoFactorRepositoryInterface $repository,
        TotpProviderInterface $totpProvider,
        BackupCodeManagerInterface $backupCodeManager,
        LoggerInterface $logger,
        string $issuer = 'WordPress',
    ) {
        $this->repository = $repository;
        $this->totpProvider = $totpProvider;
        $this->backupCodeManager = $backupCodeManager;
        $this->logger = $logger;
        $this->issuer = $issuer;
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
}
