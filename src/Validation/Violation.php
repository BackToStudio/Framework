<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation;

/**
 * Represents a single validation violation.
 */
final class Violation
{
    public function __construct(
        private readonly string $field,
        private readonly string $message,
        private readonly string $constraint,
    ) {
    }

    public function getField(): string
    {
        return $this->field;
    }

    public function getMessage(): string
    {
        return $this->message;
    }

    public function getConstraint(): string
    {
        return $this->constraint;
    }

    /**
     * @return array{field: string, message: string, constraint: string}
     */
    public function toArray(): array
    {
        return [
            'field' => $this->field,
            'message' => $this->message,
            'constraint' => $this->constraint,
        ];
    }
}
