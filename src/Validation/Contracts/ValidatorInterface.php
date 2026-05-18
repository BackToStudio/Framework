<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation\Contracts;

use BackTo\Framework\Validation\ValidationResult;

/**
 * Validates data against a set of rules.
 *
 * Port interface — domain and application layers depend on this contract.
 */
interface ValidatorInterface
{
    /**
     * Validate an associative array of data against a rules map.
     *
     * @param array<string, mixed>                          $data  Key-value data to validate.
     * @param array<string, ConstraintInterface|ConstraintInterface[]> $rules Map of field name to constraint(s).
     */
    public function validate(array $data, array $rules): ValidationResult;

    /**
     * Validate a single value against one or more constraints.
     *
     * @param ConstraintInterface|ConstraintInterface[] $constraints
     */
    public function validateValue(mixed $value, ConstraintInterface|array $constraints): ValidationResult;
}
