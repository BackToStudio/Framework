# RestApi

REST API route management with autoconfiguration.

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
