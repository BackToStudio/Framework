<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\ValueObject;

use InvalidArgumentException;
use Stringable;

final readonly class MetaKey implements Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        if ($value === '') {
            throw new InvalidArgumentException('A meta key must not be empty.');
        }

        $this->value = $value;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function isProtected(): bool
    {
        return str_starts_with($this->value, '_');
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
