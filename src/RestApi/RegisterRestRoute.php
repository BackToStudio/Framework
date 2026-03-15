<?php

declare(strict_types=1);

namespace BackTo\Framework\RestApi;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\RestApi\Contracts\RestRouteRegistrarInterface;

class RegisterRestRoute implements Hooks
{
    private RestRouteRegistry $registry;
    private RestRouteRegistrarInterface $registrar;
    private HookDispatcherInterface $hookDispatcher;

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
                $args['permission_callback'] = '__return_true';
            }

            $this->registrar->register(
                $route->getNamespace(),
                $route->getRoute(),
                $args,
            );
        }
    }
}
