# Register a block using block.json

For blocks with JavaScript (edit UI, save function), point to a `block.json` directory:

```php
<?php

namespace MyPlugin\Blocks;

use BackTo\Framework\Bundle\Blocks\CustomBlock;

final class HeroBlock extends CustomBlock
{
    protected string $name = 'my-plugin/hero';

    public function getArgs(): array
    {
        return [
            '__dir' => __DIR__ . '/hero', // directory containing block.json
        ];
    }
}
```

The `block.json` file defines attributes, supports, editor/view scripts, and styles:

```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 3,
    "name": "my-plugin/hero",
    "title": "Hero Section",
    "category": "design",
    "icon": "cover-image",
    "attributes": {
        "heading": { "type": "string" },
        "backgroundUrl": { "type": "string" }
    },
    "supports": {
        "align": ["wide", "full"],
        "color": { "background": true, "text": true },
        "spacing": { "padding": true }
    },
    "editorScript": "file:./index.js",
    "style": "file:./style-index.css"
}
```
