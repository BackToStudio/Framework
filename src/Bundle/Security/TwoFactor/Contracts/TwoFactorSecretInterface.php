<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\TwoFactor\Contracts;

/**
 * TOTP secret storage.
 */
interface TwoFactorSecretInterface
{
    public function getSecret(int $userId): ?string;

    public function setSecret(int $userId, string $secret): void;

    public function deleteSecret(int $userId): void;
}
