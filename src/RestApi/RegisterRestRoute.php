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
use BackTo\Framework\Validation\Contracts\ValidatedRestRouteInterface;
use BackTo\Framework\Validation\Contracts\ValidatorInterface;
use BackTo\Framework\Validation\Validator;

final class RegisterRestRoute implements Hooks
{
    private readonly RestRouteRegistry $registry;
    private readonly RestRouteRegistrarInterface $registrar;
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly UserContextInterface $userContext;
    private readonly ValidatorInterface $validator;

    public function __construct(
        RestRouteRegistry $registry,
        RestRouteRegistrarInterface $registrar,
        HookDispatcherInterface $hookDispatcher,
        UserContextInterface $userContext,
        ValidatorInterface $validator,
    ) {
        $this->registry = $registry;
        $this->registrar = $registrar;
        $this->hookDispatcher = $hookDispatcher;
        $this->userContext = $userContext;
        $this->validator = $validator;
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
                'callback' => self::adaptHandler($route, $this->validator),
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
     * optionally validates the payload, calls the route handler,
     * and converts RestResponse → WP_REST_Response.
     */
    private static function adaptHandler(RestRouteInterface $route, ValidatorInterface $validator): \Closure
    {
        return static function (\WP_REST_Request $wpRequest) use ($route, $validator): \WP_REST_Response {
            $params = $wpRequest->get_params();
            $method = $wpRequest->get_method();
            $body = $wpRequest->get_body();

            /** @var array<string, string> $headers */
            $headers = array_map(
                static fn (array|string $v): string => \is_array($v) ? ($v[0] ?? '') : $v,
                $wpRequest->get_headers(),
            );

            // Validate request parameters when the route declares rules.
            if ($route instanceof ValidatedRestRouteInterface) {
                $rules = $route->rules();

                if ($rules !== []) {
                    $result = $validator->validate($params, $rules);

                    if (!$result->isValid()) {
                        return new \WP_REST_Response(
                            [
                                'code' => 'validation_error',
                                'message' => 'Validation failed.',
                                'errors' => $result->toArray(),
                            ],
                            400,
                        );
                    }
                }
            }

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
