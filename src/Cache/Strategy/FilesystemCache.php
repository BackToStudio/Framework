<?php

declare(strict_types=1);

namespace BackTo\Framework\Cache\Strategy;

use BackToVendor\Symfony\Component\Filesystem\Filesystem;
use DateInterval;

/**
 * PSR-16 cache backed by the filesystem.
 *
 * Each cache entry is stored as a serialized PHP file.
 * Uses Symfony Filesystem (already scoped) for file operations.
 */
final class FilesystemCache extends AbstractCache
{
    private readonly Filesystem $filesystem;
    private readonly string $directory;

    public function __construct(string $directory, ?Filesystem $filesystem = null)
    {
        $this->directory = rtrim($directory, '/');
        $this->filesystem = $filesystem ?? new Filesystem();

        if (!$this->filesystem->exists($this->directory)) {
            $this->filesystem->mkdir($this->directory, 0755);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->validateKey($key);

        $file = $this->getFilePath($key);

        if (!$this->filesystem->exists($file)) {
            return $default;
        }

        $data = $this->readEntry($file);

        if ($data === null) {
            return $default;
        }

        if ($data['expiry'] !== null && $data['expiry'] < time()) {
            $this->filesystem->remove($file);

            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, null|int|DateInterval $ttl = null): bool
    {
        $this->validateKey($key);

        $seconds = $this->ttlToSeconds($ttl);

        if ($seconds !== null && $seconds <= 0) {
            return $this->delete($key);
        }

        $data = [
            'value' => $value,
            'expiry' => $seconds !== null ? time() + $seconds : null,
        ];

        $file = $this->getFilePath($key);
        $this->filesystem->dumpFile($file, serialize($data));

        return true;
    }

    public function delete(string $key): bool
    {
        $this->validateKey($key);

        $file = $this->getFilePath($key);

        if ($this->filesystem->exists($file)) {
            $this->filesystem->remove($file);
        }

        return true;
    }

    public function clear(): bool
    {
        if ($this->filesystem->exists($this->directory)) {
            $this->filesystem->remove($this->directory);
            $this->filesystem->mkdir($this->directory, 0755);
        }

        return true;
    }

    public function has(string $key): bool
    {
        $this->validateKey($key);

        $file = $this->getFilePath($key);

        if (!$this->filesystem->exists($file)) {
            return false;
        }

        $data = $this->readEntry($file);

        if ($data === null) {
            return false;
        }

        if ($data['expiry'] !== null && $data['expiry'] < time()) {
            $this->filesystem->remove($file);

            return false;
        }

        return true;
    }

    private function getFilePath(string $key): string
    {
        return $this->directory . '/' . md5($key) . '.cache';
    }

    /**
     * @return array{value: mixed, expiry: int|null}|null
     */
    private function readEntry(string $file): ?array
    {
        $content = file_get_contents($file);

        if ($content === false) {
            return null;
        }

        $data = @\unserialize($content, ['allowed_classes' => false]);

        if (!is_array($data) || !array_key_exists('value', $data) || !array_key_exists('expiry', $data)) {
            return null;
        }

        return $data;
    }
}
