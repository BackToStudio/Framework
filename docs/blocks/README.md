# Blocks Bundle

Register custom Gutenberg blocks and block styles through the DI container.

## Overview

The Blocks bundle provides abstract base classes for defining blocks and block styles, registries to collect them, and hook actions to register them with WordPress. It also includes an action to replace `<img>` tags pointing to SVG files with inline SVG markup.

```php
<?php

use BackTo\Framework\Bundle\Blocks\BlocksExtension;

$kernel->addExtension(new BlocksExtension());
```

## Documentation

| Document | Type | Description |
|---|---|---|
| [Getting started](tutorial.md) | Tutorial | Register your first custom block and block style |
| [Common tasks](how-to/README.md) | How-to | SVG inlining, multi-block styles, duplicate handling |
| [API reference](reference.md) | Reference | Abstract classes, interfaces, registries, actions |
| [Architecture](explanation.md) | Explanation | Registration flow, static vs dynamic blocks |
