<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation\Constraint;

final class Callback extends AbstractConstraint
{
    /** @var callable(mixed): ?string */
    private $callback;

    /**
     * @param callable(mixed): ?string $callback Returns null when valid, error message when invalid.
     */
    public function __construct(
        callable $callback,
        string $message = '',
    ) {
        parent::__construct($message);
        $this->callback = $callback;
    }

    public function getName(): string
    {
        return 'callback';
    }

    protected function getDefaultMessage(): string
    {
        return 'This value is not valid.';
    }

    public function validate(mixed $value): ?string
    {
        $result = ($this->callback)($value);

        if ($result === null) {
            return null;
        }

        return $this->message !== '' ? $this->formatMessage() : $result;
    }
}
