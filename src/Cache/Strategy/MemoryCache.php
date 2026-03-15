<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Strategy;

use DateInterval;

class MemoryCache extends AbstractCache
{
    /** @var array<string, mixed> */
    private array $store = [];

    /** @var array<string, float|null> */
    private array $expiries = [];

    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);

        if (!$this->has($key)) {
            return $default;
        }

        return $this->store[$key];
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->validateKey($key);

        $seconds = $this->ttlToSeconds($ttl);

        if ($seconds !== null && $seconds <= 0) {
            return $this->delete($key);
        }

        $this->store[$key] = $value;
        $this->expiries[$key] = $seconds !== null ? microtime(true) + $seconds : null;

        return true;
    }

    public function delete(string $key): bool
    {
        $this->validateKey($key);

        unset($this->store[$key], $this->expiries[$key]);

        return true;
    }

    public function clear(): bool
    {
        $this->store = [];
        $this->expiries = [];

        return true;
    }

    public function has(string $key): bool
    {
        $this->validateKey($key);

        if (!array_key_exists($key, $this->store)) {
            return false;
        }

        if ($this->expiries[$key] !== null && $this->expiries[$key] < microtime(true)) {
            $this->delete($key);

            return false;
        }

        return true;
    }
}
