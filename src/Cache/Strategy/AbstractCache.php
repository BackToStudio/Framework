<?php

namespace BackTo\Framework\Cache\Strategy;

use BackTo\Framework\Cache\Contracts\CacheInterface;
use BackTo\Framework\Cache\Contracts\InvalidArgumentException;
use DateInterval;
use DateTime;

abstract class AbstractCache implements CacheInterface
{
    /**
     * Validate a cache key according to PSR-16 rules.
     *
     * @throws InvalidArgumentException
     */
    protected function validateKey(string $key): void
    {
        if ($key === '') {
            throw new InvalidArgumentException('Cache key must not be empty.');
        }

        if (preg_match('/[{}()\/@:\\\\]/', $key)) {
            throw new InvalidArgumentException(
                sprintf('Cache key "%s" contains reserved characters: {}()/\@:', $key)
            );
        }
    }

    /**
     * Convert a TTL value to seconds.
     *
     * @param null|int|DateInterval $ttl
     * @return int|null Seconds, or null for no expiration.
     */
    protected function ttlToSeconds(null|int|DateInterval $ttl): ?int
    {
        if ($ttl === null) {
            return null;
        }

        if ($ttl instanceof DateInterval) {
            $reference = new DateTime();
            $end = clone $reference;
            $end->add($ttl);

            return $end->getTimestamp() - $reference->getTimestamp();
        }

        return $ttl;
    }

    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        $result = [];
        foreach ($keys as $key) {
            $result[$key] = $this->get($key, $default);
        }

        return $result;
    }

    public function setMultiple(iterable $values, null|int|DateInterval $ttl = null): bool
    {
        $success = true;
        foreach ($values as $key => $value) {
            if (!$this->set($key, $value, $ttl)) {
                $success = false;
            }
        }

        return $success;
    }

    public function deleteMultiple(iterable $keys): bool
    {
        $success = true;
        foreach ($keys as $key) {
            if (!$this->delete($key)) {
                $success = false;
            }
        }

        return $success;
    }
}
