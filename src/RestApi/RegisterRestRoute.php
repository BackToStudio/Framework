<?php

declare(strict_types=1);

namespace BackTo\Framework\RestApi;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\RestApi\Contracts\RestRouteRegistrarInterface;

class RegisterRestRoute implements Hooks
{
    private readonly RestRouteRegistry $registry;
    private readonly RestRouteRegistrarInterface $registrar;
    private readonly HookDispatcherInterface $hookDispatcher;

    public function __construct(
        RestRouteRegistry $registry,
        RestRouteRegistrarInterface $registrar,
        HookDispatcherInterface $hookDispatcher,
    ) {
        $this->registry = $registry;
        $this->registrar = $registrar;
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('rest_api_init', [$this, 'registerRoutes']);
    }

    protected function isUserLoggedIn(): bool
    {
        if (function_exists('is_user_logged_in')) {
            return is_user_logged_in();
        }

        return false;
    }

    /**
     * Default permission callback: require authenticated user.
     */
    public function requireAuthentication(): bool
    {
        return $this->isUserLoggedIn();
    }

    public function registerRoutes(): void
    {
        foreach ($this->registry->getRoutes() as $route) {
            $args = [
                'methods' => $route->getMethods(),
                'callback' => [$route, 'handle'],
            ];

            $permissionCallback = $route->getPermissionCallback();
            if ($permissionCallback !== null) {
                $args['permission_callback'] = $permissionCallback;
            } else {
                $args['permission_callback'] = [$this, 'requireAuthentication'];
            }

            $this->registrar->register(
                $route->getNamespace(),
                $route->getRoute(),
                $args,
            );
        }
    }
}
