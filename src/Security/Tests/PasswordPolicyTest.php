<?php

declare(strict_types=1);

namespace BackTo\Framework\Security\Tests;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Security\PasswordPolicy;
use PHPUnit\Framework\TestCase;

class PasswordPolicyTest extends TestCase
{
    private PasswordPolicy $rule;

    protected function setUp(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $this->rule = new PasswordPolicy($dispatcher);
    }

    public function testImplementsRequiredInterfaces(): void
    {
        $this->assertInstanceOf(Hooks::class, $this->rule);
        $this->assertInstanceOf(SecurityRuleInterface::class, $this->rule);
    }

    public function testGetName(): void
    {
        $this->assertSame('password_policy', $this->rule->getName());
    }

    public function testHooksRegistersActions(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);

        $dispatcher->expects($this->exactly(2))
            ->method('addAction')
            ->willReturnCallback(function (string $hook) {
                $this->assertContains($hook, ['user_profile_update_errors', 'registration_errors']);
            });

        $rule = new PasswordPolicy($dispatcher);
        $rule->hooks();
    }

    public function testValidStrongPassword(): void
    {
        $errors = $this->rule->validate('MyStr0ng!Pass');
        $this->assertEmpty($errors);
    }

    public function testPasswordTooShort(): void
    {
        $errors = $this->rule->validate('Ab1!');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('12 characters', $errors[0]);
    }

    public function testPasswordMissingUppercase(): void
    {
        $errors = $this->rule->validate('mystrongpass1!');
        $this->assertContains('Password must contain at least one uppercase letter.', $errors);
    }

    public function testPasswordMissingNumber(): void
    {
        $errors = $this->rule->validate('MyStrongPass!!');
        $this->assertContains('Password must contain at least one number.', $errors);
    }

    public function testPasswordMissingSpecialChar(): void
    {
        $errors = $this->rule->validate('MyStrongPass12');
        $this->assertContains('Password must contain at least one special character.', $errors);
    }

    public function testMultipleViolations(): void
    {
        $errors = $this->rule->validate('abc');
        $this->assertCount(4, $errors);
    }

    public function testCustomMinLength(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new PasswordPolicy($dispatcher, 8);

        $errors = $rule->validate('Ab1!cdef');
        $this->assertEmpty($errors);
        $this->assertSame(8, $rule->getMinLength());
    }

    public function testDisabledRequirements(): void
    {
        $dispatcher = $this->createMock(HookDispatcherInterface::class);
        $rule = new PasswordPolicy($dispatcher, 4, false, false, false);

        $errors = $rule->validate('abcd');
        $this->assertEmpty($errors);
    }

    public function testDefaultMinLength(): void
    {
        $this->assertSame(12, $this->rule->getMinLength());
    }
}
