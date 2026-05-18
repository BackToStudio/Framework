<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation\Constraint;

final class Count extends AbstractConstraint
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
        return 'count';
    }

    protected function getDefaultMessage(): string
    {
        if ($this->min !== null && $this->max !== null) {
            return 'This collection must contain between %d and %d elements.';
        }

        if ($this->min !== null) {
            return 'This collection must contain at least %d elements.';
        }

        return 'This collection must contain at most %d elements.';
    }

    public function validate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!\is_array($value) && !$value instanceof \Countable) {
            return 'This value must be an array or countable.';
        }

        $count = \count($value);

        if ($this->min !== null && $count < $this->min) {
            if ($this->max !== null) {
                return $this->formatMessage($this->min, $this->max);
            }

            return $this->formatMessage($this->min);
        }

        if ($this->max !== null && $count > $this->max) {
            if ($this->min !== null) {
                return $this->formatMessage($this->min, $this->max);
            }

            return $this->formatMessage($this->max);
        }

        return null;
    }
}
