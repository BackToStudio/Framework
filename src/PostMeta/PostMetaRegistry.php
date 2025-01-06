<?php

namespace BackTo\Framework\PostMeta;

use BackTo\Framework\PostMeta\Contracts\PostMetaInterface;

class PostMetaRegistry
{
    /**
     * @var array
     */
    private static $postMetas = [];

    /**
     * Register a post meta
     *
     * @param PostMetaInterface $postMeta
     * @return void
     */
    public static function register(PostMetaInterface $postMeta): void
    {
        self::$postMetas[$postMeta->getKey()] = $postMeta;
    }

    /**
     * Get a registered post meta by key
     *
     * @param string $key
     * @return PostMetaInterface|null
     */
    public static function get(string $key): ?PostMetaInterface
    {
        return self::$postMetas[$key] ?? null;
    }

    /**
     * Get all registered post metas
     *
     * @return array
     */
    public static function all(): array
    {
        return self::$postMetas;
    }

    /**
     * Check if a post meta is registered
     *
     * @param string $key
     * @return bool
     */
    public static function has(string $key): bool
    {
        return isset(self::$postMetas[$key]);
    }

    /**
     * Remove a registered post meta
     *
     * @param string $key
     * @return void
     */
    public static function remove(string $key): void
    {
        unset(self::$postMetas[$key]);
    }

    /**
     * Clear all registered post metas
     *
     * @return void
     */
    public static function clear(): void
    {
        self::$postMetas = [];
    }
} 