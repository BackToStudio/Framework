# Performance

A zero-configuration performance bundle that optimizes page delivery, asset loading, and server configuration for WordPress sites.

## Overview

The Performance module provides full-page caching, HTML/CSS/JS minification, script deferral, image optimization, `.htaccess` tuning, and WordPress bloat removal. Most optimizations are enabled by default. Opt-in features (page cache, HTML minification, unused CSS removal) are activated through a fluent configurator.

Configure the bundle in `config/performance.php`:

```php
<?php

use BackTo\Framework\Bundle\Performance\PerformanceConfigurator;

return static function (PerformanceConfigurator $performance): void {
    $performance
        ->pageCacheEnabled(true)
        ->pageCacheTtl(3600)
        ->minifyHtml(true);
};
```

## Documentation

| Document | Type | Description |
|---|---|---|
| [Getting started](tutorial.md) | Tutorial | Enable page caching and see a cache hit step by step |
| [Common tasks](how-to/README.md) | How-to | Practical recipes for real-world performance tuning |
| [API reference](reference/README.md) | Reference | Complete interface, class, and configuration documentation |
| [Architecture](explanation/README.md) | Explanation | Design decisions, cache lifecycle, minification pipeline |
