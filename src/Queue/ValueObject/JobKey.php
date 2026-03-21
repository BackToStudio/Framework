<?php

declare(strict_types=1);

namespace BackTo\Framework\Queue\ValueObject;

use InvalidArgumentException;
use Stringable;

/**
 * Represents a validated job key identifier.
 */
final readonly class JobKey implements Stringable
{
    private const MAX_LENGTH = 255;

    public string $value;

    public function __construct(string $value)
    {
        if (trim($value) === '') {
            throw new InvalidArgumentException('Job key cannot be empty.');
        }

        if (\strlen($value) > self::MAX_LENGTH) {
            throw new InvalidArgumentException(\sprintf(
                'Job key cannot exceed %d characters.',
                self::MAX_LENGTH
            ));
        }

        if (preg_match('/^[a-zA-Z0-9_.\-]+$/', $value) !== 1) {
            throw new InvalidArgumentException(\sprintf(
                'Job key "%s" contains invalid characters. Only alphanumeric, underscore, dot, and hyphen are allowed.',
                $value
            ));
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

    public function __toString(): string
    {
        return $this->value;
    }
}
