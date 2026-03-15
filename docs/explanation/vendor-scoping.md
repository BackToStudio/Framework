# Vendor Scoping with php-scoper

## The WordPress conflict problem

WordPress plugins share a single global PHP namespace. If two plugins both bundle Symfony DependencyInjection 5.3 but at different patch versions, PHP will fatal error on the second `class_exists` check — whichever plugin loads first wins.

This is a well-known problem in the WordPress ecosystem. Solutions include:

- Mozart (namespace prefixing)
- Strauss (namespace prefixing)
- **php-scoper** (full namespace rewriting)

## How BackTo Framework handles it

The framework uses [humbug/php-scoper](https://github.com/humbug/php-scoper) to rewrite all vendor namespaces under a `BackToVendor\` prefix:

| Original namespace | Scoped namespace |
|-------------------|-----------------|
| `Symfony\Component\DependencyInjection\` | `BackToVendor\Symfony\Component\DependencyInjection\` |
| `Symfony\Component\Config\` | `BackToVendor\Symfony\Component\Config\` |
| `Symfony\Component\Filesystem\` | `BackToVendor\Symfony\Component\Filesystem\` |
| `Symfony\Contracts\Service\` | `BackToVendor\Symfony\Contracts\Service\` |
| `Psr\Container\` | `BackToVendor\Psr\Container\` |

## Where scoped files live

Scoped files are stored in `vendor-scoped/` (committed to the repository):

```
vendor-scoped/
├── psr/container/
├── symfony/config/
├── symfony/dependency-injection/
├── symfony/filesystem/
└── symfony/service-contracts/
```

The `composer.json` autoload section maps these scoped namespaces:

```json
{
    "autoload": {
        "psr-4": {
            "BackToVendor\\Symfony\\Component\\DependencyInjection\\": "vendor-scoped/symfony/dependency-injection/",
            "BackToVendor\\Psr\\Container\\": "vendor-scoped/psr/container/"
        }
    }
}
```

## Regenerating scoped vendors

The `composer prefix-vendor` script handles the full pipeline:

```bash
composer prefix-vendor
```

This:
1. Removes old `vendor-scoped/`
2. Installs php-scoper via composer-bin-plugin
3. Runs php-scoper for each dependency with its own config file

Individual scoping configs live in `config/php-scoper/`:

```
config/php-scoper/
├── symfony-dependency-injection.inc.php
├── symfony-config.inc.php
├── symfony-filesystem.inc.php
├── symfony-service-contracts.inc.php
└── psr-container.inc.php
```

## Impact on code

Throughout the framework, imports use the scoped namespace:

```php
// NOT this:
use Symfony\Component\DependencyInjection\ContainerBuilder;

// But this:
use BackToVendor\Symfony\Component\DependencyInjection\ContainerBuilder;
```

This is a one-time adjustment. All framework code consistently uses `BackToVendor\` prefixed classes.

## Trade-offs

**Pros:**
- Zero risk of conflict with other plugins using Symfony
- Works with any version of the scoped dependencies
- Full compatibility with WordPress multisite and plugin ecosystems

**Cons:**
- `vendor-scoped/` must be committed (or generated at build time)
- Debugging requires awareness of the prefix
- IDE autocompletion may need extra configuration for the scoped namespaces
