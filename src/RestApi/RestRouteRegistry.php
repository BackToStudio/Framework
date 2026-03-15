<?php

declare(strict_types=1);

namespace BackTo\Framework\RestApi;

use BackTo\Framework\Contracts\RegistryInterface;
use BackTo\Framework\RestApi\Contracts\RestRouteInterface;

class RestRouteRegistry implements RegistryInterface
{
    /** @var RestRouteInterface[] */
    private array $routes = [];

    public function add(RestRouteInterface $route): self
    {
        $this->routes[] = $route;

        return $this;
    }

    /**
     * @return RestRouteInterface[]
     */
    public function getRoutes(): array
    {
        return $this->routes;
    }
}
