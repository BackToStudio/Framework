# Work with Options

The Options module provides a clean interface over WordPress `wp_options`.

## Inject the repository

```php
namespace App\Service;

use BackTo\Framework\Options\Contracts\OptionsRepositoryInterface;

class SiteSettings
{
    public function __construct(
        private OptionsRepositoryInterface $options
    ) {}

    public function getSiteLogo(): string
    {
        return (string) $this->options->get('site_logo', '');
    }

    public function updateSiteLogo(string $url): bool
    {
        return $this->options->update('site_logo', sanitize_url($url));
    }
}
```

The `OptionsRepositoryInterface` is automatically bound to `WordPressOptionsRepository` via the DI container.

## Available methods

| Method | Description |
|--------|-------------|
| `get(string $key, mixed $default = null)` | Get an option value |
| `update(string $key, mixed $value)` | Create or update an option |
| `delete(string $key)` | Delete an option |
| `exists(string $key)` | Check if an option exists |

## Important

- **Sanitize values before storing.** The repository delegates directly to WordPress functions — you are responsible for sanitization.
- WordPress automatically serializes/unserializes complex values (arrays, objects).
- Use `exists()` instead of checking `get()` against `null`, since `null` could be a stored value.
