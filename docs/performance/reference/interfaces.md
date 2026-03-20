# Interfaces

### `PageCacheInterface`

**Namespace:** `BackTo\Framework\Bundle\Performance\Contracts`

| Method | Return | Description |
|---|---|---|
| `get(string $url)` | `?string` | Cached HTML, or `null` on miss |
| `put(string $url, string $html, int $ttl)` | `void` | Store a page with TTL in seconds |
| `invalidate(string $url)` | `void` | Remove a cached page by URL |
| `flush()` | `void` | Remove all cached pages |

### `HtmlOptimizerInterface`

**Namespace:** `BackTo\Framework\Bundle\Performance\Contracts`

| Method | Return | Description |
|---|---|---|
| `optimize(string $html)` | `string` | Optimize HTML content (minification, cleanup) |

### `DatabaseOptimizerInterface`

**Namespace:** `BackTo\Framework\Bundle\Performance\Contracts`

| Method | Return | Description |
|---|---|---|
| `cleanup()` | `array<string, int>` | Run all cleanup tasks. Returns rows affected per task |
| `optimizeTables()` | `int` | Run `OPTIMIZE TABLE` on all prefixed tables. Returns count |
