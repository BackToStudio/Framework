# Configure auto-update policy

The framework controls WordPress automatic updates via `AutoUpdatePolicy`, a security rule that hooks into WordPress core filters.

## Via SecurityConfigurator (recommended)

Configure auto-updates in your project's `config/security.php`:

```php
<?php

use BackTo\Framework\Bundle\Security\SecurityConfigurator;

return static function (SecurityConfigurator $security): void {
    $security
        ->autoUpdateMajorCore(false)          // Block major WP updates (6.x → 7.0)
        ->autoUpdateMinorCore(true)           // Allow minor/security updates (6.7.1 → 6.7.2)
        ->autoUpdatePlugins(false)            // Block plugin auto-updates
        ->autoUpdateThemes(false)             // Block theme auto-updates
        ->autoUpdateTranslations(true)        // Allow translation updates
        ->autoUpdateAllowedPlugins([          // Allowlist specific plugins
            'akismet/akismet.php',
            'wordpress-seo/wp-seo.php',
        ])
        ->autoUpdateAllowedThemes([           // Allowlist specific themes
            'twentytwentyfive',
        ]);
};
```

## Default policy

| Component | Auto-update | Rationale |
|-----------|-------------|-----------|
| Core minor | Enabled | Security patches, low risk |
| Core major | Disabled | Breaking changes, requires testing |
| Plugins | Disabled | May break site, test in staging first |
| Themes | Disabled | May break design, test in staging first |
| Translations | Enabled | Low risk, no code changes |

## Allowlist mechanism

When plugins/themes auto-updates are globally disabled, you can allowlist specific items that are safe to auto-update:

```php
$security
    ->autoUpdatePlugins(false)                     // Global: off
    ->autoUpdateAllowedPlugins(['akismet/akismet.php']); // Exception: Akismet auto-updates
```

The allowlist takes precedence over the global setting.

## Via direct service access

For programmatic access at runtime:

```php
use BackTo\Framework\Bundle\Security\Hardening\AutoUpdatePolicy;

$autoUpdate = $container->get(AutoUpdatePolicy::class);

$autoUpdate->setMajorCore(false);
$autoUpdate->setMinorCore(true);
$autoUpdate->setPlugins(false);
$autoUpdate->setThemes(false);
$autoUpdate->setTranslations(true);

$autoUpdate->setAllowedPlugins(['akismet/akismet.php']);
$autoUpdate->setAllowedThemes(['twentytwentyfour']);

// Inspect current policy
$policy = $autoUpdate->getPolicy();
// ['major_core' => false, 'minor_core' => true, 'plugins' => false, ...]
```

## WordPress filters controlled

| Filter | Controls |
|--------|----------|
| `allow_major_auto_core_updates` | Major WordPress core versions |
| `allow_minor_auto_core_updates` | Minor/security core updates |
| `auto_update_plugin` | Per-plugin auto-updates |
| `auto_update_theme` | Per-theme auto-updates |
| `auto_update_translation` | Translation updates |
