# Configuration Reference

## Project structure

```
my-project/
├── config/
│   └── services.php          # Project service definitions
├── src/
│   ├── Kernel.php            # Extends ThemeKernel or PluginKernel
│   ├── PostType/             # Custom post types
│   ├── Taxonomy/             # Custom taxonomies
│   └── ...
├── var/                      # Generated container cache
│   ├── container.php
│   └── container.meta.php
├── composer.json
└── functions.php             # (theme) or plugin.php (plugin)
```

## config/ directory

The `config/` directory supports the main `services.php` plus optional per-module configuration files:

```
config/
├── services.php          # Main service definitions (required)
├── performance.php       # Performance module overrides (optional)
└── security.php          # Security module overrides (optional)
```

Module config files use dedicated **configurator** objects with typed, fluent APIs — no need to know parameter key names.

## config/services.php

The main service configuration file. Must return a closure receiving a `ContainerConfigurator`:

```php
<?php

use BackToVendor\Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;

return function (ContainerConfigurator $configurator) {
    $services = $configurator->services()
        ->defaults()
        ->autowire()
        ->autoconfigure();

    $services->load('MyApp\\', '../src/*')
        ->exclude('../src/{Entity,Tests}');
};
```

## Kernel instantiation

### Theme

```php
$kernel = new MyTheme\Kernel('production', false);
$kernel->setTextDomain('my-theme');
$kernel->load();
```

### Plugin

```php
$kernel = new MyPlugin\Kernel('production', false);
$kernel->setTextDomain('my-plugin');
$kernel->load();
```

### Constructor parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$environment` | `string` | Environment name (e.g. `'production'`, `'development'`) |
| `$debug` | `bool` | `true` to rebuild the container on every request |

## Debug mode

When `$debug` is `true`:
- The DI container is rebuilt on every request (no cache)
- Exceptions are thrown instead of silently caught

When `$debug` is `false`:
- The container is cached in `var/container.php`
- Exceptions are caught and the framework fails silently

## Available DI parameters

### ThemeKernel

| Parameter | Value |
|-----------|-------|
| `%themeDirectory%` | Project root directory (auto-detected) |
| `%themeTextDomain%` | Text domain set via `setTextDomain()` |

### PluginKernel

| Parameter | Value |
|-----------|-------|
| `%pluginDirectory%` | Project root directory (auto-detected) |
| `%pluginTextDomain%` | Text domain set via `setTextDomain()` |

### Framework defaults

These parameters are set by `FrameworkConfiguration` and can be overridden in your `config/services.php`:

```php
$container->parameters()->set('framework.cache.ttl', 7200);
```

| Parameter | Default | Description |
|-----------|---------|-------------|
| `framework.cache.ttl` | `3600` | Default cache TTL in seconds |
| `framework.cache.enabled` | `true` | Enable/disable cache |
| `framework.seo.title_separator` | `\|` | SEO title separator |
| `framework.seo.robots_default` | `index, follow` | Default robots meta |
| `framework.rest_api.default_namespace` | `app/v1` | Default REST namespace |
| `framework.rest_api.default_per_page` | `10` | Default results per page |
| `framework.assets.version_strategy` | `file` | Asset versioning strategy |
| `framework.observability.log_level` | `error` | Minimum log level |
| `framework.observability.performance_tracking` | `false` | Enable performance collection |

### Performance defaults (config/performance.php)

Performance parameters are managed by `PerformanceConfiguration` and overridden via the fluent `PerformanceConfigurator` in `config/performance.php`:

```php
<?php

use BackTo\Framework\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance
        ->pageCacheEnabled(true)
        ->pageCacheTtl(7200)
        ->minifyHtml(true)
        ->htaccessGzip(false);
};
```

| Parameter | Default | Configurator method |
|-----------|---------|---------------------|
| `performance.clean_head` | `true` | `cleanHead(bool)` |
| `performance.disable_emojis` | `true` | `disableEmojis(bool)` |
| `performance.disable_embeds` | `true` | `disableEmbeds(bool)` |
| `performance.disable_xmlrpc` | `true` | `disableXmlrpc(bool)` |
| `performance.heartbeat.disable_frontend` | `true` | `heartbeatDisableFrontend(bool)` |
| `performance.heartbeat.admin_interval` | `60` | `heartbeatAdminInterval(int)` |
| `performance.defer_scripts` | `true` | `deferScripts(bool)` |
| `performance.defer_exclude` | `['jquery-core', 'jquery-migrate']` | `deferExclude(array)` |
| `performance.remove_query_strings` | `true` | `removeQueryStrings(bool)` |
| `performance.lazy_load_skip_first` | `1` | `lazyLoadSkipFirst(int)` |
| `performance.add_decoding_async` | `true` | `addDecodingAsync(bool)` |
| `performance.add_fetchpriority` | `true` | `addFetchpriority(bool)` |
| `performance.minify_html` | `false` | `minifyHtml(bool)` |
| `performance.resource_hints.preconnect` | `[]` | `preconnect(array)` |
| `performance.resource_hints.dns_prefetch` | `[]` | `dnsPrefetch(array)` |
| `performance.resource_hints.preload` | `[]` | `preload(array)` |
| `performance.revisions_limit` | `5` | `revisionsLimit(int)` |
| `performance.woocommerce_optimize` | `true` | `woocommerceOptimize(bool)` |
| `performance.page_cache.enabled` | `false` | `pageCacheEnabled(bool)` |
| `performance.page_cache.ttl` | `3600` | `pageCacheTtl(int)` |
| `performance.db_cleanup.revisions_limit` | `5` | `dbCleanupRevisionsLimit(int)` |
| `performance.cache_preload.enabled` | `true` | `cachePreloadEnabled(bool)` |
| `performance.cache_preload.delay` | `5` | `cachePreloadDelay(int)` |
| `performance.cache_preload.batch_size` | `50` | `cachePreloadBatchSize(int)` |
| `performance.htaccess.gzip` | `true` | `htaccessGzip(bool)` |
| `performance.htaccess.browser_cache` | `true` | `htaccessBrowserCache(bool)` |
| `performance.htaccess.remove_etags` | `true` | `htaccessRemoveEtags(bool)` |
| `performance.htaccess.keep_alive` | `true` | `htaccessKeepAlive(bool)` |
| `performance.htaccess.static_ttl` | `31536000` | `htaccessStaticTtl(int)` |

### Security defaults (config/security.php)

Security parameters are managed by `SecurityConfiguration` and overridden via the fluent `SecurityConfigurator` in `config/security.php`:

```php
<?php

use BackTo\Framework\Security\SecurityConfigurator;

return static function (SecurityConfigurator $security): void {
    $security
        ->passwordMinLength(16)
        ->twoFactorEnabled(true)
        ->twoFactorIssuer('MonApp');
};
```

| Parameter | Default | Configurator method |
|-----------|---------|---------------------|
| `security.headers_enabled` | `true` | `headersEnabled(bool)` |
| `security.xmlrpc_disabled` | `true` | `xmlrpcDisabled(bool)` |
| `security.hide_version` | `true` | `hideVersion(bool)` |
| `security.csp_report_only` | `false` | `cspReportOnly(bool)` |
| `security.password_min_length` | `12` | `passwordMinLength(int)` |
| `security.max_concurrent_sessions` | `1` | `maxConcurrentSessions(int)` |
| `security.rest_api_require_auth` | `true` | `restApiRequireAuth(bool)` |
| `security.disable_file_editor` | `true` | `disableFileEditor(bool)` |
| `security.two_factor_enabled` | `false` | `twoFactorEnabled(bool)` |
| `security.two_factor_issuer` | `'WordPress'` | `twoFactorIssuer(string)` |

## Binding parameters in services.php

Use `bind()` to inject scalar parameters:

```php
$services = $configurator->services()
    ->defaults()
    ->bind('$themeDirectory', '%themeDirectory%')
    ->bind('$themeTextDomain', '%themeTextDomain%')
    ->autowire()
    ->autoconfigure();
```

## Container cache

The compiled container is stored in `var/container.php`. To clear it:

```bash
rm -f var/container.php var/container.meta.php
```

This happens automatically on `composer dump-autoload` (via the `post-autoload-dump` script).

## PHPStan configuration

```yaml
# phpstan.neon
includes:
    - vendor/szepeviktor/phpstan-wordpress/extension.neon
    - phpstan-baseline.neon
parameters:
    level: 6
    paths: [src]
    excludePaths: [src/*/Tests/*]
```

## PHPUnit configuration

Test suites are defined per module in `phpunit.xml`:

```xml
<testsuite name="post-type">
    <directory>src/PostType/Tests</directory>
</testsuite>
```

Run all tests: `vendor/bin/phpunit`
Run a single suite: `vendor/bin/phpunit --testsuite post-type`
