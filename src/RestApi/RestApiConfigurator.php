<?php

declare(strict_types=1);

namespace BackTo\Framework\RestApi;

use BackTo\Framework\Contracts\ModuleConfiguratorInterface;

/**
 * Fluent configurator for REST API module parameters.
 *
 * Used in config/rest-api.php to override defaults without
 * knowing the underlying parameter key names:
 *
 *     return static function (RestApiConfigurator $restApi): void {
 *         $restApi
 *             ->defaultNamespace('custom/v2')
 *             ->defaultPerPage(25);
 *     };
 */
final class RestApiConfigurator implements ModuleConfiguratorInterface
{
    /** @var array<string, mixed> */
    private array $overrides = [];

    public function defaultNamespace(string $namespace): self
    {
        $this->overrides['rest_api.default_namespace'] = $namespace;

        return $this;
    }

    public function defaultPerPage(int $perPage): self
    {
        $this->overrides['rest_api.default_per_page'] = $perPage;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toParameters(): array
    {
        return $this->overrides;
    }
}
