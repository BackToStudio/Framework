# Plugin Bundle

The bootstrap kernel for WordPress plugins built with the BackTo Framework.

## Overview

The Plugin bundle provides `PluginKernel`, which manages the plugin lifecycle: DI container compilation, service loading, and text domain registration. It supports both standard plugins and mu-plugins with dedicated translation loaders.

```php
<?php

use BackTo\Framework\Bundle\Plugin\PluginKernel;

$kernel = new PluginKernel(
    environment: 'production',
    debug: false,
);

$kernel->setProjectDir(__DIR__);
$kernel->setTextDomain('my-plugin');
$kernel->boot();
```

## Documentation

| Document | Type | Description |
|---|---|---|
| [Getting started](tutorial.md) | Tutorial | Bootstrap your first plugin with PluginKernel |
| [Common tasks](how-to/README.md) | How-to | Mu-plugin translations, environment config, service files |
| [API reference](reference/README.md) | Reference | Kernel methods, I18n classes, interfaces |
| [Architecture](explanation/README.md) | Explanation | Kernel lifecycle, DI parameters, plugin vs mu-plugin |
