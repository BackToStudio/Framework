<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Security\Contracts\ClientIpResolverInterface;
use BackTo\Framework\Security\Contracts\LoginThrottleInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Security\LoginHardening;
use PHPUnit\Framework\TestCase;

/**
 * Testable subclass that avoids WP_Error dependency.
 */
class TestableLoginHardening extends LoginHardening
{
    private bool $lockoutErrorCreated = false;

    protected function createLockoutError(int $remainingSeconds): mixed
    {
        $this->lockoutErrorCreated = true;
        $error = new \stdClass();
        $error->code = 'too_many_attempts';
        $error->remainingSeconds = $remainingSeconds;

        /** @phpstan-ignore return.type */
        return $error;
    }

    public function wasLockoutErrorCreated(): bool
    {
        return $this->lockoutErrorCreated;
    }
}

class LoginHardeningTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private LoginThrottleInterface $throttle;
    private LoggerInterface $logger;
    private ClientIpResolverInterface $ipResolver;
    private TestableLoginHardening $rule;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->throttle = $this->createMock(LoginThrottleInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->ipResolver = $this->createMock(ClientIpResolverInterface::class);
        $this->ipResolver->method('getClientIp')->willReturn('127.0.0.1');
        $this->rule = new TestableLoginHardening($this->dispatcher, $this->throttle, $this->logger, $this->ipResolver);
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->rule);
        $this->assertInstanceOf(SecurityRuleInterface::class, $this->rule);
    }

    public function testGetName(): void
    {
        $this->assertSame('login_hardening', $this->rule->getName());
    }

    public function testHooksRegistersFiltersAndActions(): void
    {
        $this->dispatcher->expects($this->exactly(2))
            ->method('addFilter')
            ->willReturnCallback(function (string $hook) {
                $this->assertContains($hook, ['authenticate', 'login_errors']);
            });

        $this->dispatcher->expects($this->exactly(2))
            ->method('addAction')
            ->willReturnCallback(function (string $hook) {
                $this->assertContains($hook, ['wp_login_failed', 'wp_login']);
            });

        $this->rule->hooks();
    }

    public function testGenericLoginError(): void
    {
        $this->assertSame(
            'The login information you have entered is incorrect.',
            $this->rule->genericLoginError()
        );
    }

    public function testThrottleLoginSkipsEmptyCredentials(): void
    {
        $this->throttle->expects($this->never())->method('isLocked');

        $result = $this->rule->throttleLogin(null, '', '');
        $this->assertNull($result);
    }

    public function testThrottleLoginReturnsUserWhenNotLocked(): void
    {
        $this->throttle->method('isLocked')->willReturn(false);
        $this->throttle->method('isAccountLocked')->willReturn(false);

        $user = new \stdClass();
        $result = $this->rule->throttleLogin($user, 'admin', 'pass');

        $this->assertSame($user, $result);
    }

    public function testThrottleLoginReturnsErrorWhenIpLocked(): void
    {
        $this->throttle->method('isLocked')->willReturn(true);
        $this->throttle->method('getLockoutRemainingSeconds')->willReturn(600);

        $this->logger->expects($this->once())->method('warning');

        $result = $this->rule->throttleLogin(null, 'admin', 'pass');

        $this->assertTrue($this->rule->wasLockoutErrorCreated());
        $this->assertSame('too_many_attempts', $result->code);
    }

    public function testThrottleLoginReturnsErrorWhenAccountLocked(): void
    {
        $this->throttle->method('isLocked')->willReturn(false);
        $this->throttle->method('isAccountLocked')->with('admin')->willReturn(true);
        $this->throttle->method('getAccountLockoutRemainingSeconds')->with('admin')->willReturn(1200);

        $this->logger->expects($this->once())->method('warning');

        $result = $this->rule->throttleLogin(null, 'admin', 'pass');

        $this->assertTrue($this->rule->wasLockoutErrorCreated());
        $this->assertSame('too_many_attempts', $result->code);
        $this->assertSame(1200, $result->remainingSeconds);
    }

    public function testOnLoginFailedRecordsIpAndAccountAttempt(): void
    {
        $this->throttle->expects($this->once())->method('recordFailedAttempt');
        $this->throttle->expects($this->once())->method('recordFailedAccountAttempt')->with('admin');
        $this->throttle->method('getFailedAttempts')->willReturn(1);
        $this->logger->expects($this->once())->method('warning');

        $this->rule->onLoginFailed('admin');
    }

    public function testOnLoginSuccessResetsIpAndAccountThrottle(): void
    {
        $this->throttle->expects($this->once())->method('reset');
        $this->throttle->expects($this->once())->method('resetAccount')->with('admin');
        $this->logger->expects($this->once())->method('info');

        $this->rule->onLoginSuccess('admin');
    }
}
