# Add resource hints (preconnect, dns-prefetch, preload)

### Preconnect

Establish early connections to third-party origins (DNS + TCP + TLS):

```php
<?php

use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance->preconnect([
        'https://fonts.googleapis.com',
        'https://fonts.gstatic.com',
    ]);
};
```

### DNS prefetch

Resolve DNS only (lighter than preconnect):

```php
$performance->dnsPrefetch([
    '//analytics.example.com',
    '//pixel.tracking.com',
]);
```

### Preload

Force early loading of critical resources. Each resource requires a URL and an `as` type. The `type` field is optional:

```php
$performance->preload([
    ['url' => '/wp-content/themes/my-theme/fonts/custom.woff2', 'as' => 'font', 'type' => 'font/woff2'],
    ['url' => '/wp-content/themes/my-theme/css/critical.css', 'as' => 'style'],
]);
```

The `crossorigin` attribute is added automatically for `font` and `fetch` resources.
