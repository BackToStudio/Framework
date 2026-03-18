<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;

final class PasswordPolicy implements Hooks, SecurityRuleInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly RequestContextInterface $requestContext;
    private readonly int $minLength;
    private readonly bool $requireUppercase;
    private readonly bool $requireNumber;
    private readonly bool $requireSpecialChar;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        RequestContextInterface $requestContext,
        int $minLength = 12,
        bool $requireUppercase = true,
        bool $requireNumber = true,
        bool $requireSpecialChar = true,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->requestContext = $requestContext;
        $this->minLength = $minLength;
        $this->requireUppercase = $requireUppercase;
        $this->requireNumber = $requireNumber;
        $this->requireSpecialChar = $requireSpecialChar;
    }

    public function getName(): string
    {
        return 'password_policy';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('user_profile_update_errors', [$this, 'validatePassword'], 10, 3);
        $this->hookDispatcher->addAction('registration_errors', [$this, 'validateRegistrationPassword'], 10, 3);
    }

    /**
     * Validate password against policy.
     *
     * @return string[] List of violation messages (empty if valid).
     */
    public function validate(string $password): array
    {
        $errors = [];

        if (mb_strlen($password) < $this->minLength) {
            $errors[] = sprintf('Password must be at least %d characters long.', $this->minLength);
        }

        if ($this->requireUppercase && preg_match('/[A-Z]/', $password) !== 1) {
            $errors[] = 'Password must contain at least one uppercase letter.';
        }

        if ($this->requireNumber && preg_match('/[0-9]/', $password) !== 1) {
            $errors[] = 'Password must contain at least one number.';
        }

        if ($this->requireSpecialChar && preg_match('/[^a-zA-Z0-9]/', $password) !== 1) {
            $errors[] = 'Password must contain at least one special character.';
        }

        return $errors;
    }

    
    public function validatePassword(mixed $errors, bool $update, mixed $user): void
    {
        if (!is_object($user) || !isset($user->user_pass)) {
            return;
        }

        if (!is_object($errors) || !method_exists($errors, 'add')) {
            return;
        }

        $violations = $this->validate($user->user_pass);

        foreach ($violations as $message) {
            $errors->add('weak_password', $message);
        }
    }

    
    public function validateRegistrationPassword(mixed $errors, string $sanitizedLogin, string $userEmail): mixed
    {
        if (!is_object($errors) || !method_exists($errors, 'add')) {
            return $errors;
        }

        $password = $this->getRegistrationPassword();

        if ($password === '') {
            return $errors;
        }

        $violations = $this->validate($password);

        foreach ($violations as $message) {
            $errors->add('weak_password', $message);
        }

        return $errors;
    }

    public function getMinLength(): int
    {
        return $this->minLength;
    }

    protected function getRegistrationPassword(): string
    {
        $password = $this->requestContext->post('pass1') ?? '';

        if (!is_string($password)) {
            return '';
        }

        return $password;
    }
}
