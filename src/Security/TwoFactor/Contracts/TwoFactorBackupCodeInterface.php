<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\TwoFactor\Contracts;

/**
 * Backup code storage.
 */
interface TwoFactorBackupCodeInterface
{
    /**
     * @return string[] Hashed backup codes.
     */
    public function getBackupCodes(int $userId): array;

    /**
     * @param string[] $hashedCodes
     */
    public function setBackupCodes(int $userId, array $hashedCodes): void;

    public function deleteBackupCodes(int $userId): void;
}
