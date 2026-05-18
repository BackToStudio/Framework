<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation\Constraint;

final class Length extends AbstractConstraint
{
    public function __construct(
        private readonly ?int $min = null,
        private readonly ?int $max = null,
        string $message = '',
    ) {
        parent::__construct($message);
    }

    public function getName(): string
    {
        return 'length';
    }

    protected function getDefaultMessage(): string
    {
        if ($this->min !== null && $this->max !== null) {
            return 'This value must be between %d and %d characters long.';
        }

        if ($this->min !== null) {
            return 'This value must be at least %d characters long.';
        }

        return 'This value must be at most %d characters long.';
    }

    public function validate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!\is_string($value)) {
            return 'This value must be a string.';
        }

        $length = \mb_strlen($value);

        if ($this->min !== null && $length < $this->min) {
            if ($this->max !== null) {
                return $this->formatMessage($this->min, $this->max);
            }

            return $this->formatMessage($this->min);
        }

        if ($this->max !== null && $length > $this->max) {
            if ($this->min !== null) {
                return $this->formatMessage($this->min, $this->max);
            }

            return $this->formatMessage($this->max);
        }

        return null;
    }
}
