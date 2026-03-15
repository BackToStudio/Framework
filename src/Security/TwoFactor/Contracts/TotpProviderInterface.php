<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\TwoFactor\Contracts;

/**
 * Port interface for TOTP (Time-based One-Time Password) operations.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc6238
 */
interface TotpProviderInterface
{
    /**
     * Generate a new random secret key.
     */
    public function generateSecret(int $length = 20): string;

    /**
     * Generate a TOTP code for the given secret at the current time.
     */
    public function generateCode(string $secret, ?int $timestamp = null): string;

    /**
     * Verify a TOTP code against a secret, allowing for time drift.
     */
    public function verifyCode(string $secret, string $code, int $discrepancy = 1): bool;

    /**
     * Build an otpauth:// URI for QR code generation.
     */
    public function getProvisioningUri(string $secret, string $accountName, string $issuer): string;
}
