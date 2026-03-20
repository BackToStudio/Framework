<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\ValueObject;

use InvalidArgumentException;
use Stringable;

/**
 * Represents a validated IPv4 or IPv6 address.
 */
final readonly class IPAddress implements Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        $filtered = filter_var($value, FILTER_VALIDATE_IP);

        if ($filtered === false) {
            throw new InvalidArgumentException(\sprintf('Invalid IP address: "%s".', $value));
        }

        $this->value = $filtered;
    }

    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function isIPv4(): bool
    {
        return filter_var($this->value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false;
    }

    public function isIPv6(): bool
    {
        return filter_var($this->value, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false;
    }

    public function isPrivate(): bool
    {
        return filter_var($this->value, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE) === false;
    }

    public function isLoopback(): bool
    {
        return $this->value === '127.0.0.1' || $this->value === '::1';
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
