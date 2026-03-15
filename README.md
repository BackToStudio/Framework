# BackTo Framework

A structured, modular WordPress framework powered by Symfony Dependency Injection.

BackTo Framework brings Clean Architecture, DDD patterns, and a compiled service container to WordPress theme and plugin development.

## Installation

```bash
composer require backto/framework
```

**Requirements:** PHP 8.1+

## Quick Start

```php
// functions.php (theme) or plugin.php (plugin)
require_once __DIR__ . '/vendor/autoload.php';

$kernel = new MyTheme\Kernel('production', false);
$kernel->setTextDomain('my-theme');
$kernel->load();
```

```php
// src/Kernel.php
namespace MyTheme;

use BackTo\Framework\Theme\ThemeKernel;

class Kernel extends ThemeKernel {}
```

See the [Build a Theme tutorial](docs/tutorials/build-a-theme.md) for a complete walkthrough.

## Documentation

The documentation follows the [Diataxis](https://diataxis.fr/) framework:

| Section | Purpose |
|---------|---------|
| [Tutorials](docs/tutorials/) | Step-by-step lessons to get started |
| [How-to Guides](docs/how-to/) | Practical guides for specific tasks |
| [Reference](docs/reference/) | Technical descriptions of modules and contracts |
| [Explanation](docs/explanation/) | Design decisions and architectural concepts |

### Tutorials

- [Build a WordPress theme](docs/tutorials/build-a-theme.md)
- [Build a WordPress plugin](docs/tutorials/build-a-plugin.md)

### How-to Guides

- [Register custom post types](docs/how-to/register-post-types.md)
- [Register custom taxonomies](docs/how-to/register-taxonomies.md)
- [Register block styles](docs/how-to/register-block-styles.md)
- [Register post meta fields](docs/how-to/register-post-meta.md)
- [Use the cache system](docs/how-to/use-cache.md)
- [Integrate SEO plugins](docs/how-to/integrate-seo.md)

### Reference

- [Architecture overview](docs/reference/architecture.md)
- [Modules reference](docs/reference/modules.md)
- [Contracts and interfaces](docs/reference/contracts.md)
- [Configuration reference](docs/reference/configuration.md)

### Explanation

- [Why a WordPress framework?](docs/explanation/why-a-framework.md)
- [Clean Architecture and DDD](docs/explanation/clean-architecture.md)
- [Vendor scoping with php-scoper](docs/explanation/vendor-scoping.md)

## Contributing

Pull requests are welcome. For major changes, please open an issue first to discuss what you would like to change.
