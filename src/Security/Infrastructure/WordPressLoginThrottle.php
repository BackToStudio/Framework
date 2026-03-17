<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Infrastructure;

use BackTo\Framework\Security\Contracts\LoginThrottleInterface;

use function get_transient;
use function set_transient;
use function delete_transient;

/**
 * WordPress adapter for login throttling using transients.
 */
final class WordPressLoginThrottle implements LoginThrottleInterface
{
    private const TRANSIENT_PREFIX = 'backto_login_attempts_';
    private const LOCKOUT_PREFIX = 'backto_login_lockout_';
    private const ACCOUNT_ATTEMPTS_PREFIX = 'backto_acct_attempts_';
    private const ACCOUNT_LOCKOUT_PREFIX = 'backto_acct_lockout_';
    private const MAX_ATTEMPTS = 5;
    private const ACCOUNT_MAX_ATTEMPTS = 10;
    private const LOCKOUT_DURATION = 900; // 15 minutes
    private const ACCOUNT_LOCKOUT_DURATION = 1800; // 30 minutes
    private const ATTEMPT_WINDOW = 600; // 10 minutes

    public function recordFailedAttempt(string $ip): void
    {
        $key = self::TRANSIENT_PREFIX . md5($ip);
        $attempts = $this->getFailedAttempts($ip) + 1;

        set_transient($key, $attempts, self::ATTEMPT_WINDOW);

        if ($attempts >= self::MAX_ATTEMPTS) {
            $lockoutKey = self::LOCKOUT_PREFIX . md5($ip);
            set_transient($lockoutKey, time(), self::LOCKOUT_DURATION);
        }
    }

    public function getFailedAttempts(string $ip): int
    {
        $key = self::TRANSIENT_PREFIX . md5($ip);
        $attempts = get_transient($key);

        return $attempts !== false ? (int) $attempts : 0;
    }

    public function isLocked(string $ip): bool
    {
        $lockoutKey = self::LOCKOUT_PREFIX . md5($ip);

        return get_transient($lockoutKey) !== false;
    }

    public function reset(string $ip): void
    {
        $hash = md5($ip);
        delete_transient(self::TRANSIENT_PREFIX . $hash);
        delete_transient(self::LOCKOUT_PREFIX . $hash);
    }

    public function getLockoutRemainingSeconds(string $ip): int
    {
        $lockoutKey = self::LOCKOUT_PREFIX . md5($ip);
        $lockoutTime = get_transient($lockoutKey);

        if ($lockoutTime === false) {
            return 0;
        }

        $remaining = self::LOCKOUT_DURATION - (time() - (int) $lockoutTime);

        return max(0, $remaining);
    }

    public function recordFailedAccountAttempt(string $username): void
    {
        $key = self::ACCOUNT_ATTEMPTS_PREFIX . md5(strtolower($username));
        $attempts = (int) (get_transient($key) ?: 0) + 1;

        set_transient($key, $attempts, self::ATTEMPT_WINDOW);

        if ($attempts >= self::ACCOUNT_MAX_ATTEMPTS) {
            $lockoutKey = self::ACCOUNT_LOCKOUT_PREFIX . md5(strtolower($username));
            set_transient($lockoutKey, time(), self::ACCOUNT_LOCKOUT_DURATION);
        }
    }

    public function isAccountLocked(string $username): bool
    {
        $lockoutKey = self::ACCOUNT_LOCKOUT_PREFIX . md5(strtolower($username));

        return get_transient($lockoutKey) !== false;
    }

    public function getAccountLockoutRemainingSeconds(string $username): int
    {
        $lockoutKey = self::ACCOUNT_LOCKOUT_PREFIX . md5(strtolower($username));
        $lockoutTime = get_transient($lockoutKey);

        if ($lockoutTime === false) {
            return 0;
        }

        $remaining = self::ACCOUNT_LOCKOUT_DURATION - (time() - (int) $lockoutTime);

        return max(0, $remaining);
    }

    public function resetAccount(string $username): void
    {
        $hash = md5(strtolower($username));
        delete_transient(self::ACCOUNT_ATTEMPTS_PREFIX . $hash);
        delete_transient(self::ACCOUNT_LOCKOUT_PREFIX . $hash);
    }
}
