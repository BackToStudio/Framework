<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation;

/**
 * Immutable result of a validation operation.
 *
 * Contains zero or more violations. A result with no violations is considered valid.
 */
final class ValidationResult
{
    /** @var Violation[] */
    private readonly array $violations;

    public function __construct(Violation ...$violations)
    {
        $this->violations = $violations;
    }

    public static function valid(): self
    {
        return new self();
    }

    public function isValid(): bool
    {
        return $this->violations === [];
    }

    /**
     * @return Violation[]
     */
    public function getViolations(): array
    {
        return $this->violations;
    }

    /**
     * Get violations for a specific field.
     *
     * @return Violation[]
     */
    public function getViolationsFor(string $field): array
    {
        return array_values(
            array_filter(
                $this->violations,
                static fn (Violation $v): bool => $v->getField() === $field,
            ),
        );
    }

    /**
     * Merge another result into this one.
     */
    public function merge(self $other): self
    {
        if ($other->isValid()) {
            return $this;
        }

        return new self(...$this->violations, ...$other->violations);
    }

    /**
     * @return array<int, array{field: string, message: string, constraint: string}>
     */
    public function toArray(): array
    {
        return array_map(
            static fn (Violation $v): array => $v->toArray(),
            $this->violations,
        );
    }
}
