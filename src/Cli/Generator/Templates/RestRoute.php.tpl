<?php

declare(strict_types=1);

namespace {{namespace}};

use BackTo\Framework\RestApi\Contracts\RestRequest;
use BackTo\Framework\RestApi\Contracts\RestResponse;
use BackTo\Framework\RestApi\Contracts\RestRouteInterface;

class {{className}} implements RestRouteInterface
{
    public function getNamespace(): string
    {
        return '{{routeNamespace}}';
    }

    public function getRoute(): string
    {
        return '{{route}}';
    }

    /**
     * @return string[]
     */
    public function getMethods(): array
    {
        return ['GET'];
    }

    public function handle(RestRequest $request): RestResponse
    {
        return new RestResponse(['message' => 'Hello from {{className}}'], 200);
    }

    public function getPermissionCallback(): ?callable
    {
        // TODO: implement permission check — returning null makes this route public.
        return null;
    }
}
