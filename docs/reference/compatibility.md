# Compatibility Matrix

## PHP versions

| PHP Version | Support Status | Notes |
|-------------|---------------|-------|
| 8.2 | Supported | Minimum required version (`composer.json`) |
| 8.3 | Supported | Tested in CI |
| 8.4 | Supported | Tested in CI |
| < 8.2 | Not supported | Uses typed properties, enums, named arguments |

## WordPress versions

| WordPress Version | Support Status | Notes |
|-------------------|---------------|-------|
| 6.4+ | Supported | Minimum recommended |
| 6.3 | Compatible | Not actively tested |
| < 6.3 | Not supported | Missing required hooks and APIs |

## Dependencies

### Runtime (vendor-scoped)

All Symfony dependencies are scoped under the `BackToVendor\` namespace via php-scoper to avoid conflicts with other plugins or themes.

| Package | Version | Scoped Namespace |
|---------|---------|-----------------|
| `symfony/dependency-injection` | ^5.3 | `BackToVendor\Symfony\Component\DependencyInjection` |
| `symfony/config` | ^5.3 | `BackToVendor\Symfony\Component\Config` |
| `symfony/filesystem` | ^5.3 | `BackToVendor\Symfony\Component\Filesystem` |
| `symfony/service-contracts` | ^2.4 | `BackToVendor\Symfony\Contracts\Service` |
| `psr/container` | 1.1.1 | `BackToVendor\Psr\Container` |

### Development only

| Package | Version | Purpose |
|---------|---------|---------|
| `phpunit/phpunit` | ^9.5 | Unit testing |
| `phpstan/phpstan` | ^2.1 | Static analysis (Level 8) |
| `php-stubs/wordpress-stubs` | ^6.9 | WordPress type stubs for PHPStan |
| `szepeviktor/phpstan-wordpress` | ^2.0 | PHPStan WordPress extension |
| `bamarni/composer-bin-plugin` | ^1.4 | Isolated tool management (php-scoper) |

## WordPress API usage

The framework uses these WordPress APIs. Ensure they are available in your environment:

| API | Used by | Since WP |
|-----|---------|----------|
| `register_post_type()` | PostType module | 2.9 |
| `register_taxonomy()` | Taxonomy module | 2.8 |
| `register_block_type()` | Block module | 5.0 |
| `register_block_style()` | Block module | 5.3 |
| `register_post_meta()` | PostMeta module | 4.9.8 |
| `register_rest_route()` | REST API module | 4.4 |
| `add_menu_page()` / `add_submenu_page()` | Admin module | 1.5 |
| `get_option()` / `update_option()` | Options module | 1.5 |
| `set_transient()` / `get_transient()` | Cache (Transient) | 2.8 |
| `WP_CLI::add_command()` | CLI module | WP-CLI 2.0 |

## Browser support

The framework is backend-only (PHP). It does not ship any frontend assets. Browser compatibility depends on your theme or plugin implementation.

## Multisite

The framework is compatible with WordPress Multisite installations. Each site in the network can use its own kernel instance with independent configuration.

Note: `TransientCache` uses `set_transient()` / `get_transient()` which are site-scoped in multisite. Use `set_site_transient()` if you need network-wide caching (not currently provided by the framework).
