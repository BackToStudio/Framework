<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation\Constraint;

use BackTo\Framework\Validation\Contracts\ConstraintInterface;

/**
 * Base class for constraints with a customizable error message.
 */
abstract class AbstractConstraint implements ConstraintInterface
{
    public function __construct(
        protected readonly string $message = '',
    ) {
    }

    /**
     * Return the constraint short name (e.g. "not_blank", "email").
     */
    abstract public function getName(): string;

    /**
     * Return the default error message when none is provided.
     */
    abstract protected function getDefaultMessage(): string;

    protected function formatMessage(string|int|float ...$args): string
    {
        $template = $this->message !== '' ? $this->message : $this->getDefaultMessage();

        return \sprintf($template, ...$args);
    }
}
