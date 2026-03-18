<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\TwoFactor\Contracts;

/**
 * Composite port interface for persisting 2FA user settings.
 *
 * Extends all segregated 2FA interfaces for backward compatibility.
 * Prefer depending on the narrowest interface your class actually needs:
 * - TwoFactorStateInterface: enable/disable state
 * - TwoFactorSecretInterface: TOTP secret storage
 * - TwoFactorBackupCodeInterface: backup code storage
 */
interface TwoFactorRepositoryInterface extends
    TwoFactorStateInterface,
    TwoFactorSecretInterface,
    TwoFactorBackupCodeInterface
{
}
