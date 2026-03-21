<?php

declare(strict_types=1);

namespace BackTo\Framework\RestApi;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\UserContextInterface;
use BackTo\Framework\RestApi\Contracts\RestRequest;
use BackTo\Framework\RestApi\Contracts\RestResponse;
use BackTo\Framework\RestApi\Contracts\RestRouteInterface;
use BackTo\Framework\RestApi\Contracts\RestRouteRegistrarInterface;

final class RegisterRestRoute implements Hooks
{
    private readonly RestRouteRegistry $registry;
    private readonly RestRouteRegistrarInterface $registrar;
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly UserContextInterface $userContext;

    public function __construct(
        RestRouteRegistry $registry,
        RestRouteRegistrarInterface $registrar,
        HookDispatcherInterface $hookDispatcher,
        UserContextInterface $userContext,
    ) {
        $this->registry = $registry;
        $this->registrar = $registrar;
        $this->hookDispatcher = $hookDispatcher;
        $this->userContext = $userContext;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('rest_api_init', [$this, 'registerRoutes']);
    }

    /**
     * Default permission callback: require authenticated user.
     */
    public function requireAuthentication(): bool
    {
        return $this->userContext->isLoggedIn();
    }

    public function registerRoutes(): void
    {
        foreach ($this->registry->getRoutes() as $route) {
            $args = [
                'methods' => $route->getMethods(),
                'callback' => self::adaptHandler($route),
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

    /**
     * Create a callback that converts WP_REST_Request → RestRequest,
     * calls the route handler, and converts RestResponse → WP_REST_Response.
     */
    private static function adaptHandler(RestRouteInterface $route): \Closure
    {
        return static function (\WP_REST_Request $wpRequest) use ($route): \WP_REST_Response {
            $params = $wpRequest->get_params();
            $method = $wpRequest->get_method();
            $body = $wpRequest->get_body();

            /** @var array<string, string> $headers */
            $headers = array_map(
                static fn (array|string $v): string => \is_array($v) ? ($v[0] ?? '') : $v,
                $wpRequest->get_headers(),
            );

            $request = new RestRequest($params, $method, $headers, $body);
            $response = $route->handle($request);

            $wpResponse = new \WP_REST_Response($response->getData(), $response->getStatusCode());

            foreach ($response->getHeaders() as $name => $value) {
                $wpResponse->header($name, $value);
            }

            return $wpResponse;
        };
    }
}
