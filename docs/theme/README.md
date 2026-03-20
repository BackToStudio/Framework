# Theme Bundle

The bootstrap kernel for WordPress themes built with the BackTo Framework, with built-in HTML `<head>` cleanup actions.

## Overview

The Theme bundle provides `ThemeKernel` for managing the theme lifecycle, automatic translation loading, and a set of cleanup actions that remove unnecessary tags from the WordPress `<head>` (RSS feeds, emojis, version number, SVG filters, navigation fallback).

```php
<?php

use BackTo\Framework\Bundle\Theme\ThemeKernel;

$kernel = new ThemeKernel(
    environment: wp_get_environment_type(),
    debug: WP_DEBUG,
);

$kernel->setProjectDir(get_template_directory());
$kernel->setTextDomain('my-theme');
$kernel->boot();
```

## Documentation

| Document | Type | Description |
|---|---|---|
| [Getting started](tutorial.md) | Tutorial | Bootstrap your first theme with ThemeKernel |
| [Common tasks](how-to/README.md) | How-to | Head cleanup, disabling actions, translations |
| [API reference](reference/README.md) | Reference | Kernel methods, cleanup actions, parameters |
| [Architecture](explanation/README.md) | Explanation | Why clean the head, action granularity, kernel differences |
