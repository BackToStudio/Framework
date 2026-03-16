<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\TwoFactor\Contracts;

/**
 * Port interface for backup code generation and verification.
 */
interface BackupCodeManagerInterface
{
    /**
     * Generate a set of single-use backup codes.
     *
     * @return string[] Plain-text codes (to show the user once).
     */
    public function generate(int $count = 8): array;

    /**
     * Hash a plain-text code for storage.
     */
    public function hash(string $code): string;

    /**
     * Verify a plain-text code against a list of hashed codes.
     *
     * @param string[] $hashedCodes
     */
    public function verify(string $code, array $hashedCodes): bool;

    /**
     * Find the index of a matching code (for removal after use).
     *
     * @param string[] $hashedCodes
     */
    public function findMatchingIndex(string $code, array $hashedCodes): ?int;
}
