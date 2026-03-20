<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\TwoFactor\Infrastructure;

use BackTo\Framework\Bundle\Security\TwoFactor\Contracts\TwoFactorRepositoryInterface;

use function delete_user_meta;
use function get_user_meta;
use function update_user_meta;

/**
 * WordPress adapter — stores 2FA settings in user meta.
 */
final class WordPressTwoFactorRepository implements TwoFactorRepositoryInterface
{
    private const META_ENABLED = '_backto_2fa_enabled';
    private const META_SECRET = '_backto_2fa_secret';
    private const META_BACKUP_CODES = '_backto_2fa_backup_codes';

    public function isEnabled(int $userId): bool
    {
        return (bool) get_user_meta($userId, self::META_ENABLED, true);
    }

    public function enable(int $userId): void
    {
        update_user_meta($userId, self::META_ENABLED, true);
    }

    public function disable(int $userId): void
    {
        update_user_meta($userId, self::META_ENABLED, false);
    }

    public function getSecret(int $userId): ?string
    {
        $secret = get_user_meta($userId, self::META_SECRET, true);

        return is_string($secret) && $secret !== '' ? $secret : null;
    }

    public function setSecret(int $userId, string $secret): void
    {
        update_user_meta($userId, self::META_SECRET, $secret);
    }

    public function deleteSecret(int $userId): void
    {
        delete_user_meta($userId, self::META_SECRET);
    }

    
    public function getBackupCodes(int $userId): array
    {
        $codes = get_user_meta($userId, self::META_BACKUP_CODES, true);

        return is_array($codes) ? $codes : [];
    }

    
    public function setBackupCodes(int $userId, array $hashedCodes): void
    {
        update_user_meta($userId, self::META_BACKUP_CODES, $hashedCodes);
    }

    public function deleteBackupCodes(int $userId): void
    {
        delete_user_meta($userId, self::META_BACKUP_CODES);
    }
}
