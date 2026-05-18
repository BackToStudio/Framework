<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation\Constraint;

use BackTo\Framework\Validation\Contracts\ConstraintInterface;

/**
 * Applies a set of constraints to each element of an array.
 */
final class Each extends AbstractConstraint
{
    /** @var ConstraintInterface[] */
    private readonly array $constraints;

    /**
     * @param ConstraintInterface|ConstraintInterface[] $constraints
     */
    public function __construct(
        ConstraintInterface|array $constraints,
        string $message = '',
    ) {
        parent::__construct($message);
        $this->constraints = \is_array($constraints) ? $constraints : [$constraints];
    }

    public function getName(): string
    {
        return 'each';
    }

    protected function getDefaultMessage(): string
    {
        return 'Element %d: %s';
    }

    public function validate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!\is_array($value)) {
            return 'This value must be an array.';
        }

        $errors = [];

        foreach ($value as $index => $item) {
            foreach ($this->constraints as $constraint) {
                $error = $constraint->validate($item);
                if ($error !== null) {
                    $errors[] = \sprintf('Element %s: %s', $index, $error);
                }
            }
        }

        if ($errors !== []) {
            return \implode(' ', $errors);
        }

        return null;
    }
}
