<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\TwoFactor\Contracts;

/**
 * 2FA enable/disable state management.
 */
interface TwoFactorStateInterface
{
    public function isEnabled(int $userId): bool;

    public function enable(int $userId): void;

    public function disable(int $userId): void;
}
