<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\TwoFactor\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Security\TwoFactor\Contracts\BackupCodeManagerInterface;
use BackTo\Framework\Security\TwoFactor\Contracts\TotpProviderInterface;
use BackTo\Framework\Security\TwoFactor\Contracts\TwoFactorRepositoryInterface;
use BackTo\Framework\Security\TwoFactor\TwoFactorAuthentication;
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
    private TestableTwoFactorAuth $rule;

    protected function setUp(): void
    {
        $this->dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->repository = $this->createMock(TwoFactorRepositoryInterface::class);
        $this->totpProvider = $this->createMock(TotpProviderInterface::class);
        $this->backupCodeManager = $this->createMock(BackupCodeManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->rule = new TestableTwoFactorAuth(
            $this->dispatcher,
            $this->repository,
            $this->totpProvider,
            $this->backupCodeManager,
            $this->logger,
            'TestApp',
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

    public function testGetIssuer(): void
    {
        $this->assertSame('TestApp', $this->rule->getIssuer());
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

    // --- Setup tests ---

    public function testSetup(): void
    {
        $this->totpProvider->method('generateSecret')->willReturn('NEWSECRET');
        $this->totpProvider->method('getProvisioningUri')
            ->with('NEWSECRET', 'user@test.com', 'TestApp')
            ->willReturn('otpauth://totp/TestApp:user@test.com?secret=NEWSECRET');

        $this->backupCodeManager->method('generate')->willReturn(['1111-2222', '3333-4444']);
        $this->backupCodeManager->method('hash')->willReturnCallback(fn ($c) => 'hashed_' . $c);

        $this->repository->expects($this->once())->method('setSecret')->with(42, 'NEWSECRET');
        $this->repository->expects($this->once())->method('setBackupCodes')
            ->with(42, ['hashed_1111-2222', 'hashed_3333-4444']);

        $result = $this->rule->setup(42, 'user@test.com');

        $this->assertSame('NEWSECRET', $result['secret']);
        $this->assertStringStartsWith('otpauth://', $result['provisioning_uri']);
        $this->assertCount(2, $result['backup_codes']);
    }

    public function testConfirmSetupSuccess(): void
    {
        $this->repository->method('getSecret')->with(42)->willReturn('SECRET');
        $this->totpProvider->method('verifyCode')->with('SECRET', '123456')->willReturn(true);

        $this->repository->expects($this->once())->method('enable')->with(42);
        $this->logger->expects($this->once())->method('info');

        $this->assertTrue($this->rule->confirmSetup(42, '123456'));
    }

    public function testConfirmSetupFailure(): void
    {
        $this->repository->method('getSecret')->with(42)->willReturn('SECRET');
        $this->totpProvider->method('verifyCode')->willReturn(false);

        $this->repository->expects($this->never())->method('enable');

        $this->assertFalse($this->rule->confirmSetup(42, '000000'));
    }

    public function testConfirmSetupNoSecret(): void
    {
        $this->repository->method('getSecret')->with(42)->willReturn(null);

        $this->assertFalse($this->rule->confirmSetup(42, '123456'));
    }

    // --- Disable tests ---

    public function testDisableForUser(): void
    {
        $this->repository->expects($this->once())->method('disable')->with(42);
        $this->repository->expects($this->once())->method('deleteSecret')->with(42);
        $this->repository->expects($this->once())->method('deleteBackupCodes')->with(42);
        $this->logger->expects($this->once())->method('info');

        $this->rule->disableForUser(42);
    }

    // --- Backup code regeneration ---

    public function testRegenerateBackupCodes(): void
    {
        $this->backupCodeManager->method('generate')->willReturn(['aaaa-bbbb', 'cccc-dddd']);
        $this->backupCodeManager->method('hash')->willReturnCallback(fn ($c) => 'h_' . $c);

        $this->repository->expects($this->once())
            ->method('setBackupCodes')
            ->with(42, ['h_aaaa-bbbb', 'h_cccc-dddd']);

        $codes = $this->rule->regenerateBackupCodes(42);

        $this->assertSame(['aaaa-bbbb', 'cccc-dddd'], $codes);
    }

    // --- Status ---

    public function testIsEnabledForUser(): void
    {
        $this->repository->method('isEnabled')
            ->willReturnCallback(fn (int $id): bool => $id === 42);

        $this->assertTrue($this->rule->isEnabledForUser(42));
        $this->assertFalse($this->rule->isEnabledForUser(99));
    }
}
