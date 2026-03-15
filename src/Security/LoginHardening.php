<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Security\Contracts\LoginThrottleInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;

class LoginHardening implements Hooks, SecurityRuleInterface
{
    use ClientIpTrait;

    private HookDispatcherInterface $hookDispatcher;
    private LoginThrottleInterface $loginThrottle;
    private LoggerInterface $logger;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        LoginThrottleInterface $loginThrottle,
        LoggerInterface $logger,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->loginThrottle = $loginThrottle;
        $this->logger = $logger;
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

        $ip = $this->getClientIp();

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
        $ip = $this->getClientIp();

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
        $ip = $this->getClientIp();
        $this->loginThrottle->reset($ip);
        $this->loginThrottle->resetAccount($username);

        $this->logger->info('Successful login', [
            'ip' => $ip,
            'username' => $username,
        ]);
    }

    /**
     * @return \WP_Error
     */
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
