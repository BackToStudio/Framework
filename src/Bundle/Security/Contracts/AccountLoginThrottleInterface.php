<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Contracts;

/**
 * Account-based login throttling.
 */
interface AccountLoginThrottleInterface
{
    public function recordFailedAccountAttempt(string $username): void;

    public function isAccountLocked(string $username): bool;

    public function getAccountLockoutRemainingSeconds(string $username): int;

    public function resetAccount(string $username): void;
}
