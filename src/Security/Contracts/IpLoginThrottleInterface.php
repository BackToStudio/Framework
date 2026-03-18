<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Contracts;

/**
 * IP-based login throttling.
 */
interface IpLoginThrottleInterface
{
    public function recordFailedAttempt(string $ip): void;

    public function getFailedAttempts(string $ip): int;

    public function isLocked(string $ip): bool;

    public function reset(string $ip): void;

    public function getLockoutRemainingSeconds(string $ip): int;
}
