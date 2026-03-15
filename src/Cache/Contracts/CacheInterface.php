<?php

namespace BackTo\Framework\Cache\Contracts;

/**
 * PSR-16 SimpleCache compatible interface.
 *
 * @see https://www.php-fig.org/psr/psr-16/
 */
interface CacheInterface
{
    /**
     * Fetches a value from the cache.
     *
     * @param string $key     The unique key of this item in the cache.
     * @param mixed  $default Default value to return if the key does not exist.
     *
     * @return mixed The value of the item from the cache, or $default in case of cache miss.
     *
     * @throws InvalidArgumentException If the $key string is not a legal value.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Persists data in the cache, uniquely referenced by a key with an optional expiration TTL time.
     *
     * @param string                $key   The key of the item to store.
     * @param mixed                 $value The value of the item to store.
     * @param null|int|\DateInterval $ttl   Optional. The TTL value of this item.
     *
     * @return bool True on success, false on failure.
     *
     * @throws InvalidArgumentException If the $key string is not a legal value.
     */
    public function set(string $key, mixed $value, null|int|\DateInterval $ttl = null): bool;

    /**
     * Delete an item from the cache by its unique key.
     *
     * @param string $key The unique cache key of the item to delete.
     *
     * @return bool True if the item was successfully removed. False if there was an error.
     *
     * @throws InvalidArgumentException If the $key string is not a legal value.
     */
    public function delete(string $key): bool;

    /**
     * Wipes clean the entire cache's keys.
     *
     * @return bool True on success, false on failure.
     */
    public function clear(): bool;

    /**
     * Determines whether an item is present in the cache.
     *
     * @param string $key The cache item key.
     *
     * @return bool
     *
     * @throws InvalidArgumentException If the $key string is not a legal value.
     */
    public function has(string $key): bool;

    /**
     * Obtains multiple cache items by their unique keys.
     *
     * @param iterable<string> $keys    A list of keys.
     * @param mixed            $default Default value to return for keys that do not exist.
     *
     * @return iterable<string, mixed> A list of key => value pairs.
     *
     * @throws InvalidArgumentException If any of the keys is not a legal value.
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable;

    /**
     * Persists a set of key => value pairs in the cache.
     *
     * @param iterable<string, mixed> $values A list of key => value pairs.
     * @param null|int|\DateInterval   $ttl    Optional. The TTL value of this item.
     *
     * @return bool True on success, false on failure.
     *
     * @throws InvalidArgumentException If any of the keys is not a legal value.
     */
    public function setMultiple(iterable $values, null|int|\DateInterval $ttl = null): bool;

    /**
     * Deletes multiple cache items in a single operation.
     *
     * @param iterable<string> $keys A list of string-based keys to be deleted.
     *
     * @return bool True if the items were successfully removed. False if there was an error.
     *
     * @throws InvalidArgumentException If any of the keys is not a legal value.
     */
    public function deleteMultiple(iterable $keys): bool;
}
