# RestApi

REST API route management with autoconfiguration.

## Classes

| Class | Role |
|-------|------|
| `RestRouteRegistry` | Collects registered REST routes |
| `RegisterRestRoute` | Application orchestrator (hooks into `rest_api_init`) |
| `Contracts\RestRouteInterface` | Interface for route definitions |
| `Contracts\RestRouteRegistrarInterface` | Port for WP registration |
| `Infrastructure\WordPressRestRouteRegistrar` | WP adapter (`register_rest_route`) |

## Contracts

### `RestRouteInterface` extends `HookInterface`

Represents a REST API route to be registered.

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
