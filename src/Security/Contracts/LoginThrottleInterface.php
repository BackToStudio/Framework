<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Contracts;

/**
 * Port interface for login attempt throttling.
 */
interface LoginThrottleInterface
{
    public function recordFailedAttempt(string $ip): void;

    public function getFailedAttempts(string $ip): int;

    public function isLocked(string $ip): bool;

    public function reset(string $ip): void;

    public function getLockoutRemainingSeconds(string $ip): int;

    public function recordFailedAccountAttempt(string $username): void;

    public function isAccountLocked(string $username): bool;

    public function getAccountLockoutRemainingSeconds(string $username): int;

    public function resetAccount(string $username): void;
}
