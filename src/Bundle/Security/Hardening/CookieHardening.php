<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Hardening;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;

/**
 * Forces Secure, HttpOnly, and SameSite attributes on WordPress auth cookies.
 *
 * Hooks into WordPress cookie-setting filters to harden:
 * - auth cookies (wordpress_logged_in_*, wordpress_sec_*)
 * - session cookies
 *
 * SameSite defaults to Lax (compatible with most setups).
 * Set to Strict for maximum protection (may break some OAuth flows).
 */
class CookieHardening implements Hooks, SecurityRuleInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;

    private bool $secure = true;
    private bool $httpOnly = true;

    /** @var 'Strict'|'Lax'|'None' */
    private string $sameSite = 'Lax';

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function getName(): string
    {
        return 'cookie_hardening';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter('secure_auth_cookie', [$this, 'forceSecure'], 10, 1);
        $this->hookDispatcher->addFilter('secure_logged_in_cookie', [$this, 'forceSecure'], 10, 1);
        $this->hookDispatcher->addAction('set_auth_cookie', [$this, 'hardenAuthCookie'], 10, 5);
        $this->hookDispatcher->addAction('set_logged_in_cookie', [$this, 'hardenLoggedInCookie'], 10, 5);
        $this->hookDispatcher->addAction('init', [$this, 'configureSessionCookie']);
    }

    public function setSecure(bool $secure): self
    {
        $this->secure = $secure;

        return $this;
    }

    public function setHttpOnly(bool $httpOnly): self
    {
        $this->httpOnly = $httpOnly;

        return $this;
    }

    public function setSameSite(string $sameSite): self
    {
        $validValues = ['Strict', 'Lax', 'None'];

        if (in_array($sameSite, $validValues, true)) {
            /** @var 'Strict'|'Lax'|'None' $sameSite */
            $this->sameSite = $sameSite;
        }

        return $this;
    }

    public function forceSecure(bool $secure): bool
    {
        return $this->secure || $secure;
    }

    /**
     * Re-set auth cookie with hardened attributes.
     */
    public function hardenAuthCookie(string $cookie, int $expire, int $expiration, int $userId, string $scheme): void
    {
        $this->setHardenedCookie($this->getAuthCookieName($scheme), $cookie, $expire);
    }

    /**
     * Re-set logged_in cookie with hardened attributes.
     */
    public function hardenLoggedInCookie(string $cookie, int $expire, int $expiration, int $userId, string $scheme): void
    {
        $this->setHardenedCookie($this->getLoggedInCookieName(), $cookie, $expire);
    }

    public function configureSessionCookie(): void
    {
        $params = $this->getSessionCookieParams();

        $this->setSessionCookieParams([
            'lifetime' => $params['lifetime'],
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $this->secure,
            'httponly' => $this->httpOnly,
            'samesite' => $this->sameSite,
        ]);
    }

    public function isSecure(): bool
    {
        return $this->secure;
    }

    public function isHttpOnly(): bool
    {
        return $this->httpOnly;
    }

    /** @return 'Strict'|'Lax'|'None' */
    public function getSameSite(): string
    {
        return $this->sameSite;
    }

    /**
     * Build the cookie options array.
     *
     * @return array{expires: int, path: string, domain: string, secure: bool, httponly: bool, samesite: 'Strict'|'Lax'|'None'}
     */
    public function buildCookieOptions(int $expire): array
    {
        return [
            'expires' => $expire,
            'path' => $this->getCookiePath(),
            'domain' => $this->getCookieDomain(),
            'secure' => $this->secure,
            'httponly' => $this->httpOnly,
            'samesite' => $this->sameSite,
        ];
    }

    protected function setHardenedCookie(string $name, string $value, int $expire): void
    {
        if ($this->headersSent()) {
            return;
        }

        $options = $this->buildCookieOptions($expire);
        setcookie($name, $value, $options);
    }

    protected function getAuthCookieName(string $scheme): string
    {
        if ($scheme === 'secure_auth' && defined('SECURE_AUTH_COOKIE')) {
            return SECURE_AUTH_COOKIE;
        }

        if (defined('AUTH_COOKIE')) {
            return AUTH_COOKIE;
        }

        return 'wordpress_sec_' . md5('default');
    }

    protected function getLoggedInCookieName(): string
    {
        if (defined('LOGGED_IN_COOKIE')) {
            return LOGGED_IN_COOKIE;
        }

        return 'wordpress_logged_in_' . md5('default');
    }

    protected function getCookiePath(): string
    {
        if (defined('COOKIEPATH')) {
            return COOKIEPATH;
        }

        return '/';
    }

    protected function getCookieDomain(): string
    {
        if (defined('COOKIE_DOMAIN')) {
            return COOKIE_DOMAIN;
        }

        return '';
    }

    protected function headersSent(): bool
    {
        return headers_sent();
    }

    /**
     * @return array{lifetime: int, path: string, domain: string, secure: bool, httponly: bool, samesite: 'Strict'|'Lax'|'None'}
     */
    protected function getSessionCookieParams(): array
    {
        $params = session_get_cookie_params();

        /** @var 'Strict'|'Lax'|'None' $sameSite */
        $sameSite = ucfirst(strtolower($params['samesite']));

        return [
            'lifetime' => $params['lifetime'],
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $sameSite,
        ];
    }

    
    protected function setSessionCookieParams(array $params): void
    {
        session_set_cookie_params($params);
    }
}
