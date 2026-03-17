<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;

class RestApiSecurity implements Hooks, SecurityRuleInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;

    /** @var string[] */
    private readonly array $additionalPublicPatterns;

    /**
     * @param string[] $additionalPublicPatterns Extra regex patterns for public routes.
     */
    public function __construct(HookDispatcherInterface $hookDispatcher, array $additionalPublicPatterns = [])
    {
        $this->hookDispatcher = $hookDispatcher;
        $this->additionalPublicPatterns = $additionalPublicPatterns;
    }

    public function getName(): string
    {
        return 'rest_api_security';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter('rest_authentication_errors', [$this, 'requireAuthentication']);
        $this->hookDispatcher->addFilter('rest_index', [$this, 'filterRestIndex']);
    }

    /**
     * Require authentication for REST API access (except whitelisted routes).
     *
     * @param mixed $result Existing authentication result.
     * @return mixed
     */
    public function requireAuthentication(mixed $result): mixed
    {
        if ($result !== null) {
            return $result;
        }

        if ($this->isPublicRoute()) {
            return $result;
        }

        if ($this->isUserAuthenticated()) {
            return $result;
        }

        return $this->createAuthenticationError();
    }

    /**
     * Remove sensitive information from REST index for unauthenticated users.
     *
     * @param mixed $response WP_REST_Response object.
     * @return mixed
     */
    public function filterRestIndex(mixed $response): mixed
    {
        if ($this->isUserAuthenticated()) {
            return $response;
        }

        if (is_object($response) && method_exists($response, 'get_data') && method_exists($response, 'set_data')) {
            /** @var \WP_REST_Response $response */
            $data = $response->get_data();
            unset($data['authentication'], $data['routes']);
            $response->set_data($data);
        }

        return $response;
    }

    
    protected function getPublicRoutePatterns(): array
    {
        return array_merge([
            '#^/oembed/#',
            '#^/wp-site-health/#',
        ], $this->additionalPublicPatterns);
    }

    protected function isPublicRoute(): bool
    {
        $route = $_SERVER['REQUEST_URI'] ?? '';

        // Extract REST route from URI
        $restPrefix = rest_get_url_prefix();
        $restPos = strpos($route, '/' . $restPrefix . '/');

        if ($restPos === false) {
            return false;
        }

        $restRoute = substr($route, $restPos + strlen($restPrefix) + 1);

        // Strip query string
        $queryPos = strpos($restRoute, '?');
        if ($queryPos !== false) {
            $restRoute = substr($restRoute, 0, $queryPos);
        }

        foreach ($this->getPublicRoutePatterns() as $pattern) {
            if (preg_match($pattern, $restRoute) === 1) {
                return true;
            }
        }

        return false;
    }

    protected function isUserAuthenticated(): bool
    {
        return function_exists('is_user_logged_in') && is_user_logged_in();
    }

    
    protected function createAuthenticationError(): mixed
    {
        return new \WP_Error(
            'rest_not_logged_in',
            'Authentication is required to access the REST API.',
            ['status' => 401]
        );
    }
}
