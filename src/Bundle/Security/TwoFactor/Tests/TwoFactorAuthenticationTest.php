<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\TwoFactor\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Contracts\LoggerInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Bundle\Security\TwoFactor\Contracts\BackupCodeManagerInterface;
use BackTo\Framework\Bundle\Security\TwoFactor\Contracts\TotpProviderInterface;
use BackTo\Framework\Bundle\Security\TwoFactor\Contracts\TwoFactorRepositoryInterface;
use BackTo\Framework\Bundle\Security\TwoFactor\TwoFactorAuthentication;
use PHPUnit\Framework\TestCase;

/**
 * Testable subclass to control WP dependencies.
 */
class TestableTwoFactorAuth extends TwoFactorAuthentication
{
    private ?string $twoFactorCode = null;
    private bool $validUser = false;
    private ?int $userId = null;
    private bool $errorCreated = false;
    private string $errorCode = '';

    public function setTwoFactorCode(?string $code): void
    {
        $this->twoFactorCode = $code;
    }

    public function setValidUser(bool $valid, ?int $userId = null): void
    {
        $this->validUser = $valid;
        $this->userId = $userId;
    }

    public function wasErrorCreated(): bool
    {
        return $this->errorCreated;
    }

    public function getLastErrorCode(): string
    {
        return $this->errorCode;
    }

    protected function getTwoFactorCode(): ?string
    {
        return $this->twoFactorCode;
    }

    protected function isValidUser(mixed $user): bool
    {
        return $this->validUser;
    }

    protected function getUserId(mixed $user): ?int
    {
        return $this->userId;
    }

    protected function createTwoFactorRequiredError(int $userId): mixed
    {
        $this->errorCreated = true;
        $this->errorCode = 'two_factor_required';

        $error = new \stdClass();
        $error->code = 'two_factor_required';
        $error->userId = $userId;

        return $error;
    }

    protected function createInvalidCodeError(): mixed
    {
        $this->errorCreated = true;
        $this->errorCode = 'two_factor_invalid';

        $error = new \stdClass();
        $error->code = 'two_factor_invalid';

        return $error;
    }
}

class TwoFactorAuthenticationTest extends TestCase
{
    private HookDispatcherInterface $dispatcher;
    private TwoFactorRepositoryInterface $repository;
    private TotpProviderInterface $totpProvider;
    private BackupCodeManagerInterface $backupCodeManager;
    private LoggerInterface $logger;
    private RequestContextInterface $requestContext;
    private TestableTwoFactorAuth $rule;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->repository = $this->createMock(TwoFactorRepositoryInterface::class);
        $this->totpProvider = $this->createMock(TotpProviderInterface::class);
        $this->backupCodeManager = $this->createMock(BackupCodeManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->requestContext = $this->createMock(RequestContextInterface::class);

        $this->rule = new TestableTwoFactorAuth(
            $this->dispatcher,
            $this->repository,
            $this->totpProvider,
            $this->backupCodeManager,
            $this->logger,
            $this->requestContext,
        );
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->rule);
        $this->assertInstanceOf(SecurityRuleInterface::class, $this->rule);
    }

    public function testGetName(): void
    {
        $this->assertSame('two_factor_authentication', $this->rule->getName());
    }

    public function testHooksRegistersFilter(): void
    {
        $this->dispatcher->expects($this->once())
            ->method('addFilter')
            ->with('authenticate', $this->anything(), 40, 3);

        $this->rule->hooks();
    }

    // --- Login interception tests ---

    public function testInterceptLoginPassesThroughInvalidUser(): void
    {
        $this->rule->setValidUser(false);
        $error = new \stdClass();

        $result = $this->rule->interceptLogin($error, 'admin', 'pass');

        $this->assertSame($error, $result);
    }

    public function testInterceptLoginPassesThroughWhen2faDisabled(): void
    {
        $this->rule->setValidUser(true, 42);
        $this->repository->method('isEnabled')->with(42)->willReturn(false);

        $user = new \stdClass();
        $result = $this->rule->interceptLogin($user, 'admin', 'pass');

        $this->assertSame($user, $result);
    }

    public function testInterceptLoginRequires2faCodeWhenEnabled(): void
    {
        $this->rule->setValidUser(true, 42);
        $this->rule->setTwoFactorCode(null);
        $this->repository->method('isEnabled')->with(42)->willReturn(true);

        $user = new \stdClass();
        $result = $this->rule->interceptLogin($user, 'admin', 'pass');

        $this->assertTrue($this->rule->wasErrorCreated());
        $this->assertSame('two_factor_required', $this->rule->getLastErrorCode());
    }

    public function testInterceptLoginAcceptsValidTotpCode(): void
    {
        $this->rule->setValidUser(true, 42);
        $this->rule->setTwoFactorCode('123456');
        $this->repository->method('isEnabled')->with(42)->willReturn(true);
        $this->repository->method('getSecret')->with(42)->willReturn('SECRET');
        $this->totpProvider->method('verifyCode')->with('SECRET', '123456')->willReturn(true);

        $this->logger->expects($this->once())->method('info');

        $user = new \stdClass();
        $result = $this->rule->interceptLogin($user, 'admin', 'pass');

        $this->assertSame($user, $result);
    }

    public function testInterceptLoginAcceptsValidBackupCode(): void
    {
        $this->rule->setValidUser(true, 42);
        $this->rule->setTwoFactorCode('1234-5678');
        $this->repository->method('isEnabled')->with(42)->willReturn(true);
        $this->repository->method('getSecret')->with(42)->willReturn('SECRET');
        $this->totpProvider->method('verifyCode')->willReturn(false);
        $this->repository->method('getBackupCodes')->with(42)->willReturn(['hashed1', 'hashed2']);
        $this->backupCodeManager->method('findMatchingIndex')
            ->with('1234-5678', ['hashed1', 'hashed2'])
            ->willReturn(0);

        $this->repository->expects($this->once())
            ->method('setBackupCodes')
            ->with(42, ['hashed2']);

        $user = new \stdClass();
        $result = $this->rule->interceptLogin($user, 'admin', 'pass');

        $this->assertSame($user, $result);
    }

    public function testInterceptLoginRejectsInvalidCode(): void
    {
        $this->rule->setValidUser(true, 42);
        $this->rule->setTwoFactorCode('000000');
        $this->repository->method('isEnabled')->with(42)->willReturn(true);
        $this->repository->method('getSecret')->with(42)->willReturn('SECRET');
        $this->totpProvider->method('verifyCode')->willReturn(false);
        $this->repository->method('getBackupCodes')->willReturn([]);
        $this->backupCodeManager->method('findMatchingIndex')->willReturn(null);

        $this->logger->expects($this->once())->method('warning');

        $this->rule->interceptLogin(new \stdClass(), 'admin', 'pass');

        $this->assertSame('two_factor_invalid', $this->rule->getLastErrorCode());
    }

}
