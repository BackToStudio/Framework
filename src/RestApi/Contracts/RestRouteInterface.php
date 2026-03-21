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
     * Handle the REST request and return a response.
     *
     * Implementations receive a framework RestRequest and must return
     * a framework RestResponse. The infrastructure layer handles
     * conversion to/from WordPress types (WP_REST_Request / WP_REST_Response).
     */
    public function handle(RestRequest $request): RestResponse;

    public function getPermissionCallback(): ?callable;
}
