<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Observability\Contracts\LoggerInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;

/**
 * Hides the default wp-login.php and wp-admin URLs behind a custom slug.
 *
 * Access to wp-login.php and wp-admin (when not logged in) returns a 404
 * unless the request uses the custom slug.
 *
 * Usage:
 *   $obfuscation->setLoginSlug('my-secret-login');
 *   // Login page is now at /my-secret-login instead of /wp-login.php
 */
class AdminUrlObfuscation implements Hooks, SecurityRuleInterface
{
    private HookDispatcherInterface $hookDispatcher;
    private LoggerInterface $logger;

    private string $loginSlug = '';

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        LoggerInterface $logger,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->logger = $logger;
    }

    public function getName(): string
    {
        return 'admin_url_obfuscation';
    }

    public function hooks(): void
    {
        if ($this->loginSlug === '') {
            return;
        }

        $this->hookDispatcher->addAction('init', [$this, 'handleCustomLoginSlug']);
        $this->hookDispatcher->addAction('wp_loaded', [$this, 'blockDefaultLogin']);
        $this->hookDispatcher->addFilter('login_url', [$this, 'filterLoginUrl'], 10, 2);
        $this->hookDispatcher->addFilter('logout_url', [$this, 'filterLogoutUrl'], 10, 2);
        $this->hookDispatcher->addFilter('site_url', [$this, 'filterSiteUrl'], 10, 3);
    }

    public function setLoginSlug(string $slug): self
    {
        $this->loginSlug = trim($slug, '/');

        return $this;
    }

    public function getLoginSlug(): string
    {
        return $this->loginSlug;
    }

    /**
     * Handle requests to the custom login slug.
     */
    public function handleCustomLoginSlug(): void
    {
        $requestUri = $this->getRequestUri();
        $slug = '/' . $this->loginSlug;

        if ($this->uriMatchesSlug($requestUri, $slug)) {
            $this->loadLoginPage();
        }
    }

    /**
     * Block direct access to wp-login.php when not using the custom slug.
     */
    public function blockDefaultLogin(): void
    {
        if ($this->isLoggedIn()) {
            return;
        }

        $requestUri = $this->getRequestUri();

        if ($this->isDefaultLoginRequest($requestUri)) {
            $this->logger->warning('Blocked direct wp-login.php access', [
                'ip' => $this->getClientIp(),
                'uri' => $requestUri,
            ]);

            $this->send404();
        }
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

    /**
     * Check if a URI matches the custom login slug.
     */
    public function uriMatchesSlug(string $requestUri, string $slug): bool
    {
        $path = parse_url($requestUri, PHP_URL_PATH);

        if (! is_string($path)) {
            return false;
        }

        $path = rtrim($path, '/');

        return $path === $slug || $path === $slug . '/';
    }

    /**
     * Check if a request is targeting the default login URL.
     */
    public function isDefaultLoginRequest(string $requestUri): bool
    {
        return str_contains($requestUri, 'wp-login.php');
    }

    protected function getRequestUri(): string
    {
        return $_SERVER['REQUEST_URI'] ?? '';
    }

    protected function getClientIp(): string
    {
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }

    protected function isLoggedIn(): bool
    {
        if (function_exists('is_user_logged_in')) {
            return is_user_logged_in();
        }

        return false;
    }

    protected function loadLoginPage(): void
    {
        if (defined('ABSPATH')) {
            require_once ABSPATH . 'wp-login.php';
            exit;
        }
    }

    protected function send404(): void
    {
        status_header(404);
        nocache_headers();

        if (function_exists('get_query_template')) {
            $template = get_query_template('404');

            if ($template !== '') {
                include $template;
            }
        }

        exit;
    }
}
