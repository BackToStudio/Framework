# Options

WordPress `wp_options` abstraction.

## Contracts

### `OptionsRepositoryInterface`

```php
interface OptionsRepositoryInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function update(string $key, mixed $value): bool;
    public function delete(string $key): bool;
    public function exists(string $key): bool;
}
```
