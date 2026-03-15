# Debug with Query Monitor

The framework uses [Query Monitor](https://querymonitor.com/) as its recommended debugging tool.

## Installation

```bash
wp plugin install query-monitor --activate
```

## What Query Monitor shows

Query Monitor adds a debug panel to the admin toolbar. Relevant panels for framework development:

| Panel | What to check |
|---|---|
| **Hooks & Actions** | Verify your `HookInterface` implementations fire at the right priority |
| **Database Queries** | Spot slow or duplicate queries from `PostMetaRepository` or custom queries |
| **HTTP API Calls** | Monitor external API calls made by your REST routes |
| **PHP Errors** | Catch notices/warnings from WordPress API calls |
| **Scripts & Styles** | Verify assets registered via `ScriptInterface` / `StyleInterface` load correctly |
| **REST API** | Debug REST route responses (`RestRouteInterface` implementations) |

## Debug mode

Enable debug mode in your Kernel to get detailed exception traces instead of silent `error_log`:

```php
class Kernel extends AbstractKernel
{
    public function __construct()
    {
        $this->debug = defined('WP_DEBUG') && WP_DEBUG;
        $this->environment = $this->debug ? 'dev' : 'prod';
    }
}
```

When `debug` is `true`:
- The DI container is rebuilt on every request (no cache)
- Exceptions propagate instead of being caught silently

## Debugging compiler passes

If a tagged service isn't being collected, check Query Monitor's **Hooks & Actions** panel to confirm that the orchestrator's `hooks()` method was called. Common issues:

1. **Missing tag** — The class doesn't implement the correct interface (e.g. `PostTypeInterface` for `wordpress.post_type`)
2. **Missing autoconfiguration** — The class isn't in a directory registered via `getBundles()`
3. **Cache stale** — Delete `var/container.php` to force a rebuild

## Useful WP_DEBUG constants

```php
// wp-config.php
define('WP_DEBUG', true);
define('WP_DEBUG_LOG', true);      // Logs to wp-content/debug.log
define('WP_DEBUG_DISPLAY', false); // Don't show errors on screen
define('SCRIPT_DEBUG', true);      // Use unminified core scripts
define('SAVEQUERIES', true);       // Required for QM database panel
```

## Tips

- Query Monitor conditionally loads only for admin users — no performance impact in production
- Use `SAVEQUERIES` only in development; it stores all queries in memory
- The QM REST API panel works with custom `RestRouteInterface` routes automatically
