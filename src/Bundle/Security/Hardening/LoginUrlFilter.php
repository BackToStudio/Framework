<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Hardening;

/**
 * Filters WordPress login/logout/site URLs to use a custom login slug.
 *
 * Pure URL-rewriting logic, extracted from AdminUrlObfuscation
 * to respect Single Responsibility Principle.
 */
final class LoginUrlFilter
{
    private string $loginSlug;

    public function __construct(string $loginSlug)
    {
        $this->loginSlug = $loginSlug;
    }

    public function filterLoginUrl(string $loginUrl, string $redirect): string
    {
        return str_replace('wp-login.php', $this->loginSlug, $loginUrl);
    }

    public function filterLogoutUrl(string $logoutUrl, string $redirect): string
    {
        return str_replace('wp-login.php', $this->loginSlug, $logoutUrl);
    }

    public function filterSiteUrl(string $url, string $path, string $scheme): string
    {
        if (str_contains($path, 'wp-login.php')) {
            return str_replace('wp-login.php', $this->loginSlug, $url);
        }

        return $url;
    }
}
