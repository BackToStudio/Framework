<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation\Constraint;

final class Type extends AbstractConstraint
{
    /**
     * @param string $type Expected type: "string", "int", "integer", "float", "double", "bool", "boolean", "array", "numeric".
     */
    public function __construct(
        private readonly string $type,
        string $message = '',
    ) {
        parent::__construct($message);
    }

    public function getName(): string
    {
        return 'type';
    }

    protected function getDefaultMessage(): string
    {
        return 'This value must be of type %s.';
    }

    public function validate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $valid = match ($this->type) {
            'string' => \is_string($value),
            'int', 'integer' => \is_int($value),
            'float', 'double' => \is_float($value) || \is_int($value),
            'bool', 'boolean' => \is_bool($value),
            'array' => \is_array($value),
            'numeric' => \is_numeric($value),
            default => $value instanceof $this->type,
        };

        if (!$valid) {
            return $this->formatMessage($this->type);
        }

        return null;
    }
}
