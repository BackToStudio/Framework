<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation\Constraint;

final class NotNull extends AbstractConstraint
{
    public function getName(): string
    {
        return 'not_null';
    }

    protected function getDefaultMessage(): string
    {
        return 'This value must not be null.';
    }

    public function validate(mixed $value): ?string
    {
        if ($value === null) {
            return $this->formatMessage();
        }

        return null;
    }
}
