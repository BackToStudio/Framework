# Register REST API Routes

The REST API module lets you define REST routes as classes, automatically registered via the DI container.

## Create a route class

Implement `RestRouteInterface`:

```php
namespace App\RestApi;

use BackTo\Framework\RestApi\Contracts\RestRouteInterface;
use WP_REST_Request;
use WP_REST_Response;

class GetEvents implements RestRouteInterface
{
    public function getNamespace(): string
    {
        return 'myapp/v1';
    }

    public function getRoute(): string
    {
        return '/events';
    }

    public function getMethods(): array
    {
        return ['GET'];
    }

    public function handle(WP_REST_Request $request): WP_REST_Response
    {
        $posts = get_posts(['post_type' => 'event', 'numberposts' => 10]);

        return new WP_REST_Response($posts, 200);
    }

    public function getPermissionCallback(): ?callable
    {
        return static fn () => current_user_can('read');
    }
}
```

The route is available at `/wp-json/myapp/v1/events`.

## Permission callbacks

Always implement `getPermissionCallback()`. Returning `null` defaults to public access.

| Use case | Callback |
|----------|----------|
| Public access | `fn () => true` |
| Logged-in users | `fn () => is_user_logged_in()` |
| Specific capability | `fn () => current_user_can('edit_posts')` |
| Nonce validation | Use `wp_verify_nonce` in the callback |

## Scaffold with WP-CLI

```bash
wp make:rest-route GetEvents --namespace=App\\RestApi --route-namespace=myapp/v1 --dir=src/RestApi
```

## How it works

1. Your class implements `RestRouteInterface` (which extends `HookInterface`)
2. The DI container autoconfigures it with the `wordpress.rest_route` tag
3. `RegisterRestRoutePass` collects all tagged services into `RestRouteRegistry`
4. `RegisterRestRoute` hooks into `rest_api_init` and calls `register_rest_route()` for each route
