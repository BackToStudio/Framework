# Options

WordPress `wp_options` abstraction.

## Classes

| Class | Role |
|-------|------|
| `Contracts\OptionsRepositoryInterface` | Port interface (get, update, delete, exists) |
| `Infrastructure\WordPressOptionsRepository` | WP adapter |

## Contracts

### `OptionsRepositoryInterface`

Port interface for WordPress options (`wp_options`).

```php
interface OptionsRepositoryInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function update(string $key, mixed $value): bool;
    public function delete(string $key): bool;
    public function exists(string $key): bool;
}
```
