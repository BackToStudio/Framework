# RestApi

REST API route management with autoconfiguration.

## Configuration

REST API parameters are managed by `RestApiConfiguration` and overridden via the fluent `RestApiConfigurator` in `config/rest-api.php`:

```php
<?php

use BackTo\Framework\RestApi\RestApiConfigurator;

return static function (RestApiConfigurator $restApi): void {
    $restApi
        ->defaultNamespace('custom/v2')
        ->defaultPerPage(25);
};
```

| Parameter | Default | Configurator method |
|-----------|---------|---------------------|
| `rest_api.default_namespace` | `app/v1` | `defaultNamespace(string)` |
| `rest_api.default_per_page` | `10` | `defaultPerPage(int)` |

## Contracts

### `RestRouteInterface` extends `HookInterface`

```php
interface RestRouteInterface extends HookInterface
{
    public function getNamespace(): string;
    public function getRoute(): string;
    /** @return string[] */
    public function getMethods(): array;
    public function handle(WP_REST_Request $request): WP_REST_Response;
    public function getPermissionCallback(): ?callable;
}
```
