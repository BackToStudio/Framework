<?php

declare(strict_types=1);

namespace BackTo\Framework\RestApi\Contracts;

use BackTo\Framework\Contracts\HookInterface;
use WP_REST_Request;
use WP_REST_Response;

/**
 * Represents a REST API route to be registered.
 */
interface RestRouteInterface extends HookInterface
{
    public function getNamespace(): string;

    public function getRoute(): string;

    
    public function getMethods(): array;

    public function handle(WP_REST_Request $request): WP_REST_Response;

    public function getPermissionCallback(): ?callable;
}
