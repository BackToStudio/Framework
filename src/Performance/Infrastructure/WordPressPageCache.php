<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Infrastructure;

use BackTo\Framework\Performance\Contracts\PageCacheInterface;
use BackToVendor\Symfony\Component\Filesystem\Filesystem;

/**
 * WordPress adapter for full-page HTML cache on the filesystem.
 *
 * Stores pre-rendered HTML pages as static files. Each cached page includes
 * an expiry timestamp checked at read time.
 *
 * File structure:
 *   {cacheDir}/{md5(url)}.html     - The cached HTML content
 *   {cacheDir}/{md5(url)}.meta     - Metadata (expiry, original URL)
 */
final class WordPressPageCache implements PageCacheInterface
{
    private readonly Filesystem $filesystem;
    private readonly string $cacheDir;

    public function __construct(string $cacheDir, ?Filesystem $filesystem = null)
    {
        $this->cacheDir = rtrim($cacheDir, '/');
        $this->filesystem = $filesystem ?? new Filesystem();

        if (!$this->filesystem->exists($this->cacheDir)) {
            $this->filesystem->mkdir($this->cacheDir, 0755);
        }
    }

    public function get(string $url): ?string
    {
        $htmlFile = $this->getHtmlPath($url);
        $metaFile = $this->getMetaPath($url);

        if (!$this->filesystem->exists($htmlFile) || !$this->filesystem->exists($metaFile)) {
            return null;
        }

        $meta = $this->readMeta($metaFile);

        if ($meta === null) {
            // Corrupted or unreadable meta — clean up orphaned files.
            $this->invalidate($url);

            return null;
        }

        if ($meta['expiry'] < time()) {
            $this->invalidate($url);

            return null;
        }

        $content = file_get_contents($htmlFile);

        return $content !== false ? $content : null;
    }

    public function put(string $url, string $html, int $ttl): void
    {
        $htmlFile = $this->getHtmlPath($url);
        $metaFile = $this->getMetaPath($url);

        $meta = [
            'url' => $url,
            'expiry' => time() + $ttl,
            'created' => time(),
        ];

        $this->filesystem->dumpFile($htmlFile, $html . "\n<!-- Cached by BackTo Framework at " . gmdate('Y-m-d H:i:s') . ' UTC -->');
        $this->filesystem->dumpFile($metaFile, serialize($meta));
    }

    public function invalidate(string $url): void
    {
        $htmlFile = $this->getHtmlPath($url);
        $metaFile = $this->getMetaPath($url);

        if ($this->filesystem->exists($htmlFile)) {
            $this->filesystem->remove($htmlFile);
        }

        if ($this->filesystem->exists($metaFile)) {
            $this->filesystem->remove($metaFile);
        }
    }

    public function flush(): void
    {
        if ($this->filesystem->exists($this->cacheDir)) {
            $this->filesystem->remove($this->cacheDir);
            $this->filesystem->mkdir($this->cacheDir, 0755);
        }
    }

    private function getHtmlPath(string $url): string
    {
        return $this->cacheDir . '/' . md5($url) . '.html';
    }

    private function getMetaPath(string $url): string
    {
        return $this->cacheDir . '/' . md5($url) . '.meta';
    }

    /**
     * @return array{url: string, expiry: int, created: int}|null
     */
    private function readMeta(string $file): ?array
    {
        $content = file_get_contents($file);

        if ($content === false) {
            return null;
        }

        $meta = @\unserialize($content, ['allowed_classes' => false]);

        if (!is_array($meta) || !isset($meta['expiry'], $meta['url'], $meta['created'])) {
            return null;
        }

        return $meta;
    }
}
