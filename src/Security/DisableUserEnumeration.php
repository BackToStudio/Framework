<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;

class DisableUserEnumeration implements Hooks, SecurityRuleInterface
{
    private HookDispatcherInterface $hookDispatcher;

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function getName(): string
    {
        return 'disable_user_enumeration';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('init', [$this, 'blockAuthorQuery']);
        $this->hookDispatcher->addFilter('rest_endpoints', [$this, 'restrictUserEndpoints']);
    }

    public function blockAuthorQuery(): void
    {
        if ($this->hookDispatcher->isAdmin()) {
            return;
        }

        if (isset($_GET['author']) || isset($_GET['author_name'])) {
            wp_safe_redirect(home_url(), 301);
            exit;
        }
    }

    /**
     * Remove /wp/v2/users endpoint for non-authenticated requests.
     *
     * @param array<string, mixed> $endpoints
     * @return array<string, mixed>
     */
    public function restrictUserEndpoints(array $endpoints): array
    {
        if ($this->isUserLoggedIn()) {
            return $endpoints;
        }

        $protectedRoutes = [
            '/wp/v2/users',
            '/wp/v2/users/(?P<id>[\d]+)',
        ];

        foreach ($protectedRoutes as $route) {
            unset($endpoints[$route]);
        }

        return $endpoints;
    }

    protected function isUserLoggedIn(): bool
    {
        return function_exists('is_user_logged_in') && is_user_logged_in();
    }
}
