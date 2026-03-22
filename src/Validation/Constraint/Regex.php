<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation\Constraint;

final class Regex extends AbstractConstraint
{
    public function __construct(
        private readonly string $pattern,
        string $message = '',
    ) {
        parent::__construct($message);
    }

    public function getName(): string
    {
        return 'regex';
    }

    protected function getDefaultMessage(): string
    {
        return 'This value does not match the expected format.';
    }

    public function validate(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (!\is_string($value)) {
            return 'This value must be a string.';
        }

        if (!\preg_match($this->pattern, $value)) {
            return $this->formatMessage();
        }

        return null;
    }
}
