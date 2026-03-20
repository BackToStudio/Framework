<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Auth;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\LoggerInterface;
use BackTo\Framework\Bundle\Security\Contracts\ClientIpResolverInterface;
use BackTo\Framework\Bundle\Security\Contracts\LoginThrottleInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;

/**
 * Hardens the WordPress login flow with throttling and generic error messages.
 *
 * Hook priorities:
 * - authenticate (30): After WordPress core auth (20), before TwoFactorAuthentication (40)
 * - login_errors (10): Standard priority for error message replacement
 * - wp_login_failed (10): Standard priority for recording failures
 * - wp_login (10): Standard priority for recording successes
 */
class LoginHardening implements Hooks, SecurityRuleInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly LoginThrottleInterface $loginThrottle;
    private readonly LoggerInterface $logger;
    private readonly ClientIpResolverInterface $ipResolver;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        LoginThrottleInterface $loginThrottle,
        LoggerInterface $logger,
        ClientIpResolverInterface $ipResolver,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->loginThrottle = $loginThrottle;
        $this->logger = $logger;
        $this->ipResolver = $ipResolver;
    }

    public function getName(): string
    {
        return 'login_hardening';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter('authenticate', [$this, 'throttleLogin'], 30, 3);
        $this->hookDispatcher->addFilter('login_errors', [$this, 'genericLoginError']);
        $this->hookDispatcher->addAction('wp_login_failed', [$this, 'onLoginFailed']);
        $this->hookDispatcher->addAction('wp_login', [$this, 'onLoginSuccess'], 10, 2);
    }

    /**
     * Block login attempts when IP is throttled.
     *
     * @return mixed The original $user, or a WP_Error when locked out.
     */
    public function throttleLogin(mixed $user, string $username, string $password): mixed
    {
        if ($username === '' || $password === '') {
            return $user;
        }

        $ip = $this->ipResolver->getClientIp();

        if ($this->loginThrottle->isLocked($ip)) {
            $remaining = $this->loginThrottle->getLockoutRemainingSeconds($ip);

            $this->logger->warning('Login attempt blocked (IP throttled)', [
                'ip' => $ip,
                'username' => $username,
                'remaining_seconds' => $remaining,
            ]);

            return $this->createLockoutError($remaining);
        }

        if ($this->loginThrottle->isAccountLocked($username)) {
            $remaining = $this->loginThrottle->getAccountLockoutRemainingSeconds($username);

            $this->logger->warning('Login attempt blocked (account throttled)', [
                'ip' => $ip,
                'username' => $username,
                'remaining_seconds' => $remaining,
            ]);

            return $this->createLockoutError($remaining);
        }

        return $user;
    }

    public function genericLoginError(): string
    {
        return 'The login information you have entered is incorrect.';
    }

    public function onLoginFailed(string $username): void
    {
        $ip = $this->ipResolver->getClientIp();

        $this->loginThrottle->recordFailedAttempt($ip);
        $this->loginThrottle->recordFailedAccountAttempt($username);

        $this->logger->warning('Failed login attempt', [
            'ip' => $ip,
            'username' => $username,
            'attempts' => $this->loginThrottle->getFailedAttempts($ip),
        ]);
    }

    public function onLoginSuccess(string $username): void
    {
        $ip = $this->ipResolver->getClientIp();
        $this->loginThrottle->reset($ip);
        $this->loginThrottle->resetAccount($username);

        $this->logger->info('Successful login', [
            'ip' => $ip,
            'username' => $username,
        ]);
    }

    
    protected function createLockoutError(int $remainingSeconds): mixed
    {
        return new \WP_Error(
            'too_many_attempts',
            sprintf(
                'Too many failed login attempts. Please try again in %d minutes.',
                (int) ceil($remainingSeconds / 60)
            )
        );
    }
}
