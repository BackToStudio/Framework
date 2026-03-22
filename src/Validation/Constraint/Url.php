<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation\Constraint;

final class Url extends AbstractConstraint
{
    public function getName(): string
    {
        return 'url';
    }

    protected function getDefaultMessage(): string
    {
        return 'This value is not a valid URL.';
    }

    public function validate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!\is_string($value)) {
            return $this->formatMessage();
        }

        if (\filter_var($value, \FILTER_VALIDATE_URL) === false) {
            return $this->formatMessage();
        }

        return null;
    }
}
