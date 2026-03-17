<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Strategy;

use BackTo\Framework\Cache\Contracts\TransientCleanerInterface;
use DateInterval;

/**
 * PSR-16 cache backed by WordPress transients.
 *
 * WordPress transients use the database (or object cache if available).
 * Keys are prefixed to avoid collisions and truncated to respect the
 * WordPress transient key limit of 172 characters.
 */
final class TransientCache extends AbstractCache
{
    private readonly string $prefix;
    private readonly TransientCleanerInterface $transientCleaner;

    public function __construct(TransientCleanerInterface $transientCleaner, string $prefix = 'btf_')
    {
        $this->transientCleaner = $transientCleaner;
        $this->prefix = $prefix;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);

        $value = \get_transient($this->prefixKey($key));

        if ($value === false) {
            return $default;
        }

        return \unserialize($value, ['allowed_classes' => false]);
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->validateKey($key);

        $seconds = $this->ttlToSeconds($ttl);

        if ($seconds !== null && $seconds <= 0) {
            return $this->delete($key);
        }

        return \set_transient(
            $this->prefixKey($key),
            serialize($value),
            $seconds ?? 0
        );
    }

    public function delete(string $key): bool
    {
        $this->validateKey($key);

        return \delete_transient($this->prefixKey($key));
    }

    public function clear(): bool
    {
        return $this->transientCleaner->clearByPrefix($this->prefix);
    }

    public function has(string $key): bool
    {
        $this->validateKey($key);

        return \get_transient($this->prefixKey($key)) !== false;
    }

    /**
     * Prefix and truncate key to respect WordPress transient name limit (172 chars).
     */
    private function prefixKey(string $key): string
    {
        $prefixed = $this->prefix . $key;

        if (strlen($prefixed) > 172) {
            $prefixed = $this->prefix . md5($key);
        }

        return $prefixed;
    }
}
