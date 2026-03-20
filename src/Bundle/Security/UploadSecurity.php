<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;

final class UploadSecurity implements Hooks, SecurityRuleInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;

    /** @var array<string, string> */
    private const MAGIC_BYTES = [
        'jpg' => "\xFF\xD8\xFF",
        'jpeg' => "\xFF\xD8\xFF",
        'png' => "\x89PNG",
        'gif' => "GIF",
        'pdf' => "%PDF",
        'zip' => "PK",
        'webp' => "RIFF",
        'svg' => "<?xml",
    ];

    /** @var string[] SVG elements and attributes that can execute scripts */
    private const SVG_DANGEROUS_TAGS = [
        'script', 'foreignObject', 'set', 'animate', 'animateTransform',
        'animateMotion', 'handler', 'listener',
    ];

    /** @var string Pattern matching event handler attributes (onload, onclick, etc.) */
    private const SVG_EVENT_HANDLER_PATTERN = '/\bon\w+\s*=/i';

    /** @var string[] */
    private const DANGEROUS_EXTENSIONS = [
        'php', 'php3', 'php4', 'php5', 'php7', 'php8', 'phtml', 'pht',
        'exe', 'sh', 'bat', 'cmd', 'com', 'cgi', 'pl', 'py',
        'asp', 'aspx', 'jsp', 'jspx',
    ];

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function getName(): string
    {
        return 'upload_security';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter('upload_mimes', [$this, 'restrictMimeTypes']);
        $this->hookDispatcher->addFilter('wp_handle_upload_prefilter', [$this, 'validateUpload']);
    }

    /**
     * @param array<string, string> $mimes
     * @return array<string, string>
     */
    public function restrictMimeTypes(array $mimes): array
    {
        foreach (self::DANGEROUS_EXTENSIONS as $ext) {
            unset($mimes[$ext]);
        }

        // Also remove compound keys containing dangerous extensions
        foreach (array_keys($mimes) as $key) {
            $extensions = explode('|', $key);
            foreach ($extensions as $ext) {
                if (in_array($ext, self::DANGEROUS_EXTENSIONS, true)) {
                    unset($mimes[$key]);
                    break;
                }
            }
        }

        return $mimes;
    }

    /**
     * @param array{name: string, type: string, tmp_name: string, error: int, size: int} $file
     * @return array{name: string, type: string, tmp_name: string, error: int|string, size: int}
     */
    public function validateUpload(array $file): array
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return $file;
        }

        $fileName = $file['name'];

        if ($this->hasDangerousExtension($fileName)) {
            $file['error'] = 'This file type is not allowed for security reasons.';

            return $file;
        }

        if ($this->hasDoubleExtension($fileName)) {
            $file['error'] = 'Files with multiple extensions are not allowed.';

            return $file;
        }

        if (!$this->validateMagicBytes($file['tmp_name'], $fileName)) {
            $file['error'] = 'File content does not match its extension.';

            return $file;
        }

        return $file;
    }

    public function hasDangerousExtension(string $fileName): bool
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        return in_array($extension, self::DANGEROUS_EXTENSIONS, true);
    }

    public function hasDoubleExtension(string $fileName): bool
    {
        $parts = explode('.', $fileName);

        if (count($parts) <= 2) {
            return false;
        }

        // Check if any non-last part is a dangerous extension
        array_pop($parts); // remove the last extension
        array_shift($parts); // remove the name part

        foreach ($parts as $part) {
            if (in_array(strtolower($part), self::DANGEROUS_EXTENSIONS, true)) {
                return true;
            }
        }

        return false;
    }

    public function validateMagicBytes(string $filePath, string $fileName): bool
    {
        $extension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));

        if (!isset(self::MAGIC_BYTES[$extension])) {
            return true; // No magic bytes to check for this extension
        }

        $fileSize = @filesize($filePath);

        if ($fileSize === false || $fileSize === 0) {
            return false; // Empty files cannot be validated
        }

        $handle = fopen($filePath, 'rb');

        if ($handle === false) {
            return false;
        }

        $bytes = fread($handle, 8);
        fclose($handle);

        if ($bytes === false) {
            return false;
        }

        $expected = self::MAGIC_BYTES[$extension];

        if (!str_starts_with($bytes, $expected)) {
            return false;
        }

        if ($extension === 'svg') {
            return $this->isSvgSafe($filePath);
        }

        return true;
    }

    /**
     * Check that an SVG file does not contain dangerous elements or attributes.
     */
    public function isSvgSafe(string $filePath): bool
    {
        $content = file_get_contents($filePath);

        if ($content === false) {
            return false;
        }

        $cdataContents = $this->extractCdataContents($content);

        return !$this->containsDangerousSvgTags($content, $cdataContents)
            && !$this->containsEventHandlers($content, $cdataContents)
            && !$this->containsDangerousUris($content, $cdataContents);
    }

    /**
     * Extract concatenated CDATA section contents from SVG markup.
     */
    private function extractCdataContents(string $content): string
    {
        if (preg_match_all('/<!\[CDATA\[(.*?)\]\]>/si', $content, $matches)) {
            return implode(' ', $matches[1]);
        }

        return '';
    }

    /**
     * Check if content or CDATA contains dangerous SVG elements.
     */
    private function containsDangerousSvgTags(string $content, string $cdataContents): bool
    {
        $contentLower = strtolower($content);
        $cdataLower = strtolower($cdataContents);

        foreach (self::SVG_DANGEROUS_TAGS as $tag) {
            if (str_contains($contentLower, '<' . $tag) || str_contains($cdataLower, '<' . $tag)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if content or CDATA contains event handler attributes.
     */
    private function containsEventHandlers(string $content, string $cdataContents): bool
    {
        return preg_match(self::SVG_EVENT_HANDLER_PATTERN, $content) === 1
            || ($cdataContents !== '' && preg_match(self::SVG_EVENT_HANDLER_PATTERN, $cdataContents) === 1);
    }

    /**
     * Check if content or CDATA contains dangerous URIs (data: or javascript:).
     */
    private function containsDangerousUris(string $content, string $cdataContents): bool
    {
        $patterns = [
            '/href\s*=\s*["\']?\s*data:/i',
            '/href\s*=\s*["\']?\s*javascript:/i',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $content) === 1) {
                return true;
            }

            if ($cdataContents !== '' && preg_match($pattern, $cdataContents) === 1) {
                return true;
            }
        }

        return false;
    }
}
