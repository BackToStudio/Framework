<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation\Constraint;

final class Email extends AbstractConstraint
{
    public function getName(): string
    {
        return 'email';
    }

    protected function getDefaultMessage(): string
    {
        return 'This value is not a valid email address.';
    }

    public function validate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!\is_string($value)) {
            return $this->formatMessage();
        }

        if (\filter_var($value, \FILTER_VALIDATE_EMAIL) === false) {
            return $this->formatMessage();
        }

        return null;
    }
}
