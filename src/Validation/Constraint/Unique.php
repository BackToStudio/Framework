<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation\Constraint;

final class Unique extends AbstractConstraint
{
    public function getName(): string
    {
        return 'unique';
    }

    protected function getDefaultMessage(): string
    {
        return 'This collection must contain only unique elements.';
    }

    public function validate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!\is_array($value)) {
            return 'This value must be an array.';
        }

        $seen = [];
        foreach ($value as $item) {
            $key = \serialize($item);
            if (isset($seen[$key])) {
                return $this->formatMessage();
            }

            $seen[$key] = true;
        }

        return null;
    }
}
