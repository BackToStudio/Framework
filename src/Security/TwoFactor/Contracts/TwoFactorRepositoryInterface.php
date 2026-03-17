<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\TwoFactor\Contracts;

/**
 * Port interface for persisting 2FA user settings.
 */
interface TwoFactorRepositoryInterface
{
    public function isEnabled(int $userId): bool;

    public function enable(int $userId): void;

    public function disable(int $userId): void;

    public function getSecret(int $userId): ?string;

    public function setSecret(int $userId, string $secret): void;

    public function deleteSecret(int $userId): void;

    /**
     * @return string[] Hashed backup codes.
     */
    public function getBackupCodes(int $userId): array;

    
    public function setBackupCodes(int $userId, array $hashedCodes): void;

    public function deleteBackupCodes(int $userId): void;
}
