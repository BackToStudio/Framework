<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation\Constraint;

final class NotBlank extends AbstractConstraint
{
    public function getName(): string
    {
        return 'not_blank';
    }

    protected function getDefaultMessage(): string
    {
        return 'This field is required.';
    }

    public function validate(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === []) {
            return $this->formatMessage();
        }

        if (\is_string($value) && \trim($value) === '') {
            return $this->formatMessage();
        }

        return null;
    }
}
