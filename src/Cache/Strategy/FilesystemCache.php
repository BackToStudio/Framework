<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Strategy;

use BackToVendor\Symfony\Component\Cache\Adapter\FilesystemAdapter;
use DateInterval;

/**
 * PSR-16 cache backed by Symfony's FilesystemAdapter.
 *
 * Delegates to the Symfony Cache component which provides:
 * - SHA256-based file naming (no MD5 collisions)
 * - Atomic writes (temp file + rename)
 * - Two-level directory sharding for performance
 * - OPcache-friendly file format
 * - Built-in pruning of expired entries
 */
final class FilesystemCache extends AbstractCache
{
    private readonly FilesystemAdapter $adapter;

    public function __construct(
        string $directory,
        string $namespace = 'btf',
        int $defaultLifetime = 0,
    ) {
        $this->adapter = new FilesystemAdapter($namespace, $defaultLifetime, $directory);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);

        $item = $this->adapter->getItem($key);

        if (!$item->isHit()) {
            return $default;
        }

        return $item->get();
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->validateKey($key);

        $seconds = $this->ttlToSeconds($ttl);

        if ($seconds !== null && $seconds <= 0) {
            return $this->delete($key);
        }

        $item = $this->adapter->getItem($key);
        $item->set($value);

        if ($seconds !== null) {
            $item->expiresAfter($seconds);
        }

        return $this->adapter->save($item);
    }

    public function delete(string $key): bool
    {
        $this->validateKey($key);

        return $this->adapter->deleteItem($key);
    }

    public function clear(): bool
    {
        return $this->adapter->clear();
    }

    public function has(string $key): bool
    {
        $this->validateKey($key);

        return $this->adapter->hasItem($key);
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $keyArray = $keys instanceof \Traversable ? iterator_to_array($keys, false) : (array) $keys;

        foreach ($keyArray as $key) {
            $this->validateKey($key);
        }

        $items = $this->adapter->getItems($keyArray);
        $result = [];

        foreach ($items as $key => $item) {
            $result[$key] = $item->isHit() ? $item->get() : $default;
        }

        return $result;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $seconds = $this->ttlToSeconds($ttl);

        if ($seconds !== null && $seconds <= 0) {
            $keys = [];
            foreach ($values as $key => $value) {
                $keys[] = $key;
            }

            return $this->deleteMultiple($keys);
        }

        foreach ($values as $key => $value) {
            $this->validateKey((string) $key);

            $item = $this->adapter->getItem((string) $key);
            $item->set($value);

            if ($seconds !== null) {
                $item->expiresAfter($seconds);
            }

            $this->adapter->saveDeferred($item);
        }

        return $this->adapter->commit();
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $keyArray = $keys instanceof \Traversable ? iterator_to_array($keys, false) : (array) $keys;

        foreach ($keyArray as $key) {
            $this->validateKey($key);
        }

        return $this->adapter->deleteItems($keyArray);
    }

    /**
     * Remove all expired cache entries from the filesystem.
     */
    public function prune(): bool
    {
        return $this->adapter->prune();
    }
}
