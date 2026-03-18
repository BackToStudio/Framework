<?php

declare(strict_types=1);

namespace BackTo\Framework\Compose\ValueObject;

use InvalidArgumentException;
use Stringable;

final readonly class Slug implements Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        if (str_contains($value, ' ')) {
            throw new InvalidArgumentException(
                sprintf('A slug must not contain spaces, got "%s".', $value)
            );
        }

        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function isEmpty(): bool
    {
        return $this->value === '';
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
