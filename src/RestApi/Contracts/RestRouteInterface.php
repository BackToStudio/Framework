<?php

declare(strict_types=1);

namespace BackTo\Framework\RestApi\Contracts;

use BackTo\Framework\Contracts\HookInterface;

/**
 * Represents a REST API route to be registered.
 */
interface RestRouteInterface extends HookInterface
{
    public function getNamespace(): string;

    public function getRoute(): string;

    /**
     * @return string[]
     */
    public function getMethods(): array;

    /**
     * Handle the REST request and return a response array or object.
     *
     * The infrastructure adapter wraps WP_REST_Request/Response transparently.
     *
     * @param mixed $request The request object (WP_REST_Request at runtime)
     * @return mixed The response (WP_REST_Response or array at runtime)
     */
    public function handle(mixed $request): mixed;

    public function getPermissionCallback(): ?callable;
}
