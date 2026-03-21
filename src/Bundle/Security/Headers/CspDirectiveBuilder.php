<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Headers;

/**
 * Builds CSP directives and the final header value.
 *
 * Single responsibility: manage the directive map and serialize it
 * into a Content-Security-Policy header string.
 */
final class CspDirectiveBuilder
{
    /** @var array<string, string[]> */
    private array $directives;

    /** @var array<string, string[]> */
    private const DEFAULT_DIRECTIVES = [
        'default-src' => ["'self'"],
        'script-src' => ["'self'"],
        'style-src' => ["'self'", "'unsafe-inline'"],
        'img-src' => ["'self'", 'data:', 'https:'],
        'font-src' => ["'self'", 'data:'],
        'connect-src' => ["'self'"],
        'frame-ancestors' => ["'self'"],
        'base-uri' => ["'self'"],
        'form-action' => ["'self'"],
    ];

    public function __construct()
    {
        $this->directives = self::DEFAULT_DIRECTIVES;
    }

    /**
     * @param string|string[] $value
     */
    public function addDirective(string $directive, string|array $value): self
    {
        $values = is_array($value) ? $value : [$value];

        if (!isset($this->directives[$directive])) {
            $this->directives[$directive] = [];
        }

        foreach ($values as $v) {
            if (!in_array($v, $this->directives[$directive], true)) {
                $this->directives[$directive][] = $v;
            }
        }

        return $this;
    }

    /**
     * @return array<string, string[]>
     */
    public function getDirectives(): array
    {
        return $this->directives;
    }

    public function buildHeaderValue(string $nonce = ''): string
    {
        $directives = $this->directives;

        if ($nonce !== '') {
            $nonceValue = "'nonce-" . $nonce . "'";

            if (isset($directives['script-src'])) {
                $directives['script-src'][] = $nonceValue;
            }
        }

        $parts = [];

        foreach ($directives as $directive => $values) {
            $parts[] = $directive . ' ' . implode(' ', $values);
        }

        return implode('; ', $parts);
    }
}
