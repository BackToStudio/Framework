<?php

declare(strict_types=1);

namespace BackTo\Framework\Performance\Hooks;

use BackTo\Framework\Contracts\ActivationHooks;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

/**
 * Inject Apache performance directives into the root .htaccess file.
 *
 * Uses WordPress's insert_with_markers() to safely add/update a
 * "BackTo Performance" block with:
 *
 * - mod_deflate: Gzip compression for text-based resources
 * - mod_expires: Browser caching with long TTLs for static assets
 * - mod_headers: Cache-Control headers, ETag removal, Keep-Alive
 * - mod_mime:    Correct MIME types for modern formats (webp, woff2, avif)
 *
 * The directives are re-applied on plugin activation and on admin_init
 * (to recover from manual edits). They are removed on deactivation.
 */
class OptimizeHtaccess implements Hooks, ActivationHooks
{
    public const MARKER = 'BackTo Performance';

    private readonly HookDispatcherInterface $hookDispatcher;

    /** @var bool Whether to enable gzip compression */
    private readonly bool $gzip;

    /** @var bool Whether to set browser cache (Expires + Cache-Control) */
    private readonly bool $browserCache;

    /** @var bool Whether to remove ETags */
    private readonly bool $removeEtags;

    /** @var bool Whether to enable Keep-Alive */
    private readonly bool $keepAlive;

    /** @var int Default cache TTL in seconds for static assets */
    private readonly int $staticTtl;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        bool $gzip = true,
        bool $browserCache = true,
        bool $removeEtags = true,
        bool $keepAlive = true,
        int $staticTtl = 31536000
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->gzip = $gzip;
        $this->browserCache = $browserCache;
        $this->removeEtags = $removeEtags;
        $this->keepAlive = $keepAlive;
        $this->staticTtl = $staticTtl;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('admin_init', [$this, 'applyDirectives']);
    }

    public function activate(): void
    {
        $this->applyDirectives();
    }

    private const HASH_TRANSIENT = 'backto_htaccess_hash';

    /**
     * Write the performance directives into the root .htaccess.
     *
     * Skips the write if directives haven't changed since last apply.
     */
    public function applyDirectives(): void
    {
        $htaccessPath = $this->getHtaccessPath();

        if ($htaccessPath === null) {
            return;
        }

        $lines = $this->buildDirectives();

        if ($lines === []) {
            return;
        }

        // Skip redundant writes by comparing a hash of the directives
        $hash = md5(implode("\n", $lines));

        if ($this->getDirectivesHash() === $hash) {
            return;
        }

        $this->insertWithMarkers($htaccessPath, self::MARKER, $lines);
        $this->storeDirectivesHash($hash);
    }

    /**
     * Remove the performance directives from .htaccess.
     *
     * Call this on plugin deactivation.
     */
    public function removeDirectives(): void
    {
        $htaccessPath = $this->getHtaccessPath();

        if ($htaccessPath === null) {
            return;
        }

        $this->insertWithMarkers($htaccessPath, self::MARKER, []);
    }

    /**
     * Build all enabled directive lines.
     *
     * @return string[]
     */
    public function buildDirectives(): array
    {
        $lines = [];

        if ($this->gzip) {
            $lines = array_merge($lines, $this->buildGzipDirectives());
        }

        if ($this->browserCache) {
            $lines = array_merge($lines, $this->buildBrowserCacheDirectives());
        }

        if ($this->removeEtags) {
            $lines = array_merge($lines, $this->buildEtagDirectives());
        }

        if ($this->keepAlive) {
            $lines = array_merge($lines, $this->buildKeepAliveDirectives());
        }

        return $lines;
    }

    /**
     * Gzip compression via mod_deflate.
     *
     * @return string[]
     */
    private function buildGzipDirectives(): array
    {
        return [
            '# Gzip compression',
            '<IfModule mod_deflate.c>',
            '    # Text-based resources',
            '    AddOutputFilterByType DEFLATE text/html',
            '    AddOutputFilterByType DEFLATE text/plain',
            '    AddOutputFilterByType DEFLATE text/css',
            '    AddOutputFilterByType DEFLATE text/xml',
            '    AddOutputFilterByType DEFLATE text/javascript',
            '    AddOutputFilterByType DEFLATE application/javascript',
            '    AddOutputFilterByType DEFLATE application/x-javascript',
            '    AddOutputFilterByType DEFLATE application/json',
            '    AddOutputFilterByType DEFLATE application/ld+json',
            '    AddOutputFilterByType DEFLATE application/xml',
            '    AddOutputFilterByType DEFLATE application/rss+xml',
            '    AddOutputFilterByType DEFLATE application/atom+xml',
            '    AddOutputFilterByType DEFLATE application/xhtml+xml',
            '',
            '    # Fonts',
            '    AddOutputFilterByType DEFLATE font/ttf',
            '    AddOutputFilterByType DEFLATE font/otf',
            '    AddOutputFilterByType DEFLATE font/opentype',
            '    AddOutputFilterByType DEFLATE application/vnd.ms-fontobject',
            '',
            '    # SVG',
            '    AddOutputFilterByType DEFLATE image/svg+xml',
            '',
            '    # Exclude already compressed files',
            '    SetEnvIfNoCase Request_URI \\.(?:gif|jpe?g|png|webp|avif|gz|bz2|zip|rar|7z|mp4|webm|mp3|ogg|woff2)$ no-gzip',
            '</IfModule>',
            '',
        ];
    }

    /**
     * Browser caching via mod_expires + Cache-Control headers.
     *
     * @return string[]
     */
    private function buildBrowserCacheDirectives(): array
    {
        $staticTtl = $this->staticTtl;
        $htmlTtl = 0; // HTML is managed by page cache, don't browser-cache it

        return [
            '# Browser caching',
            '<IfModule mod_expires.c>',
            '    ExpiresActive On',
            '',
            '    # HTML — no browser cache (managed by page cache)',
            '    ExpiresByType text/html "access plus ' . $htmlTtl . ' seconds"',
            '',
            '    # CSS & JavaScript',
            '    ExpiresByType text/css "access plus ' . $staticTtl . ' seconds"',
            '    ExpiresByType text/javascript "access plus ' . $staticTtl . ' seconds"',
            '    ExpiresByType application/javascript "access plus ' . $staticTtl . ' seconds"',
            '    ExpiresByType application/x-javascript "access plus ' . $staticTtl . ' seconds"',
            '',
            '    # Images',
            '    ExpiresByType image/jpeg "access plus ' . $staticTtl . ' seconds"',
            '    ExpiresByType image/png "access plus ' . $staticTtl . ' seconds"',
            '    ExpiresByType image/gif "access plus ' . $staticTtl . ' seconds"',
            '    ExpiresByType image/webp "access plus ' . $staticTtl . ' seconds"',
            '    ExpiresByType image/avif "access plus ' . $staticTtl . ' seconds"',
            '    ExpiresByType image/svg+xml "access plus ' . $staticTtl . ' seconds"',
            '    ExpiresByType image/x-icon "access plus ' . $staticTtl . ' seconds"',
            '',
            '    # Fonts',
            '    ExpiresByType font/ttf "access plus ' . $staticTtl . ' seconds"',
            '    ExpiresByType font/otf "access plus ' . $staticTtl . ' seconds"',
            '    ExpiresByType font/woff "access plus ' . $staticTtl . ' seconds"',
            '    ExpiresByType font/woff2 "access plus ' . $staticTtl . ' seconds"',
            '    ExpiresByType application/font-woff "access plus ' . $staticTtl . ' seconds"',
            '    ExpiresByType application/font-woff2 "access plus ' . $staticTtl . ' seconds"',
            '    ExpiresByType application/vnd.ms-fontobject "access plus ' . $staticTtl . ' seconds"',
            '',
            '    # Data interchange',
            '    ExpiresByType application/json "access plus 0 seconds"',
            '    ExpiresByType application/ld+json "access plus 0 seconds"',
            '    ExpiresByType application/xml "access plus 0 seconds"',
            '    ExpiresByType text/xml "access plus 0 seconds"',
            '',
            '    # Media',
            '    ExpiresByType audio/ogg "access plus ' . $staticTtl . ' seconds"',
            '    ExpiresByType video/mp4 "access plus ' . $staticTtl . ' seconds"',
            '    ExpiresByType video/webm "access plus ' . $staticTtl . ' seconds"',
            '</IfModule>',
            '',
            '# Cache-Control headers',
            '<IfModule mod_headers.c>',
            '    # Static assets: immutable + long cache',
            '    <FilesMatch "\\.(css|js|jpg|jpeg|png|gif|webp|avif|svg|ico|woff|woff2|ttf|otf|eot)$">',
            '        Header set Cache-Control "public, max-age=' . $staticTtl . ', immutable"',
            '    </FilesMatch>',
            '',
            '    # HTML: no browser caching (server page cache handles this)',
            '    <FilesMatch "\\.(html|htm)$">',
            '        Header set Cache-Control "no-cache, no-store, must-revalidate"',
            '    </FilesMatch>',
            '',
            '    # Data: no caching',
            '    <FilesMatch "\\.(json|xml)$">',
            '        Header set Cache-Control "no-cache, no-store, must-revalidate"',
            '    </FilesMatch>',
            '</IfModule>',
            '',
        ];
    }

    /**
     * Remove ETags to prevent unnecessary revalidation.
     *
     * @return string[]
     */
    private function buildEtagDirectives(): array
    {
        return [
            '# Remove ETags',
            '<IfModule mod_headers.c>',
            '    Header unset ETag',
            '</IfModule>',
            'FileETag None',
            '',
        ];
    }

    /**
     * Enable Keep-Alive for persistent connections.
     *
     * @return string[]
     */
    private function buildKeepAliveDirectives(): array
    {
        return [
            '# Keep-Alive',
            '<IfModule mod_headers.c>',
            '    Header set Connection keep-alive',
            '</IfModule>',
            '',
        ];
    }

    /**
     * Get the path to the root .htaccess file.
     */
    protected function getHtaccessPath(): ?string
    {
        if (!function_exists('get_home_path')) {
            return null;
        }

        $homePath = \get_home_path();

        if ($homePath === '') {
            return null;
        }

        return rtrim($homePath, '/') . '/.htaccess';
    }

    /**
     * Write markers into the .htaccess file.
     *
     * @param string[] $lines
     */
    protected function insertWithMarkers(string $path, string $marker, array $lines): bool
    {
        if (!function_exists('insert_with_markers')) {
            return false;
        }

        return \insert_with_markers($path, $marker, $lines);
    }

    protected function getDirectivesHash(): ?string
    {
        if (!function_exists('get_transient')) {
            return null;
        }

        $hash = \get_transient(self::HASH_TRANSIENT);

        return is_string($hash) ? $hash : null;
    }

    protected function storeDirectivesHash(string $hash): void
    {
        if (function_exists('set_transient')) {
            \set_transient(self::HASH_TRANSIENT, $hash, DAY_IN_SECONDS);
        }
    }
}
