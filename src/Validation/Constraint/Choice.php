<?php

declare(strict_types=1);

namespace BackTo\Framework\Validation\Constraint;

final class Choice extends AbstractConstraint
{
    /** @var list<mixed> */
    private readonly array $choices;

    /**
     * @param list<mixed> $choices
     */
    public function __construct(
        array $choices,
        string $message = '',
    ) {
        parent::__construct($message);
        $this->choices = $choices;
    }

    public function getName(): string
    {
        return 'choice';
    }

    protected function getDefaultMessage(): string
    {
        return 'The value "%s" is not a valid choice. Valid choices: %s.';
    }

    public function validate(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        if (!\in_array($value, $this->choices, true)) {
            return $this->formatMessage(
                (string) $value,
                \implode(', ', \array_map('strval', $this->choices)),
            );
        }

        return null;
    }
}
