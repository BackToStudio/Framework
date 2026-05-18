<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation\Contracts;

/**
 * A validation constraint that can be applied to a value.
 *
 * Port interface — domain and application layers depend on this contract.
 */
interface ConstraintInterface
{
    /**
     * Validate the given value against this constraint.
     *
     * Returns null when valid, or an error message string when invalid.
     */
    public function validate(mixed $value): ?string;
}
