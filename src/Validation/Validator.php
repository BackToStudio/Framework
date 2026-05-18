<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation;

use BackTo\Framework\Validation\Constraint\AbstractConstraint;
use BackTo\Framework\Validation\Contracts\ConstraintInterface;
use BackTo\Framework\Validation\Contracts\ValidatorInterface;

/**
 * Default validator implementation.
 *
 * Validates data arrays against constraint maps and single values against constraints.
 */
final class Validator implements ValidatorInterface
{
    public function validate(array $data, array $rules): ValidationResult
    {
        $violations = [];

        foreach ($rules as $field => $constraints) {
            $constraints = \is_array($constraints) ? $constraints : [$constraints];
            $value = $data[$field] ?? null;

            foreach ($constraints as $constraint) {
                $error = $constraint->validate($value);

                if ($error !== null) {
                    $violations[] = new Violation(
                        $field,
                        $error,
                        $this->getConstraintName($constraint),
                    );
                }
            }
        }

        return new ValidationResult(...$violations);
    }

    public function validateValue(mixed $value, ConstraintInterface|array $constraints): ValidationResult
    {
        $constraints = \is_array($constraints) ? $constraints : [$constraints];
        $violations = [];

        foreach ($constraints as $constraint) {
            $error = $constraint->validate($value);

            if ($error !== null) {
                $violations[] = new Violation(
                    '',
                    $error,
                    $this->getConstraintName($constraint),
                );
            }
        }

        return new ValidationResult(...$violations);
    }

    private function getConstraintName(ConstraintInterface $constraint): string
    {
        if ($constraint instanceof AbstractConstraint) {
            return $constraint->getName();
        }

        $class = \get_class($constraint);
        $pos = \strrpos($class, '\\');

        return $pos !== false ? \substr($class, $pos + 1) : $class;
    }
}
