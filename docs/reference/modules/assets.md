# Assets

Media and SVG utilities.

## Configuration

Assets parameters are managed by `AssetsConfiguration` and overridden via the fluent `AssetsConfigurator` in `config/assets.php`:

```php
<?php

use BackTo\Framework\Assets\AssetsConfigurator;

return static function (AssetsConfigurator $assets): void {
    $assets->versionStrategy('timestamp');
};
```

| Parameter | Default | Configurator method |
|-----------|---------|---------------------|
| `assets.version_strategy` | `file` | `versionStrategy(string)` |
