<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation\Constraint;

final class Range extends AbstractConstraint
{
    public function __construct(
        private readonly int|float|null $min = null,
        private readonly int|float|null $max = null,
        string $message = '',
    ) {
        parent::__construct($message);
    }

    public function getName(): string
    {
        return 'range';
    }

    protected function getDefaultMessage(): string
    {
        if ($this->min !== null && $this->max !== null) {
            return 'This value must be between %s and %s.';
        }

        if ($this->min !== null) {
            return 'This value must be %s or more.';
        }

        return 'This value must be %s or less.';
    }

    public function validate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!\is_numeric($value)) {
            return 'This value must be numeric.';
        }

        $numeric = (float) $value;

        if ($this->min !== null && $numeric < $this->min) {
            if ($this->max !== null) {
                return $this->formatMessage((string) $this->min, (string) $this->max);
            }

            return $this->formatMessage((string) $this->min);
        }

        if ($this->max !== null && $numeric > $this->max) {
            if ($this->min !== null) {
                return $this->formatMessage((string) $this->min, (string) $this->max);
            }

            return $this->formatMessage((string) $this->max);
        }

        return null;
    }
}
