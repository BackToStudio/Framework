# Getting started with the Blocks Bundle

*Tutorial — Learning-oriented*

This tutorial walks you through registering a custom Gutenberg block and a block style. By the end, both will be available in the WordPress editor.

## Prerequisites

- A WordPress plugin or theme using the BackTo Framework
- The framework's DI container configured

## Step 1: Register the extension

Add `BlocksExtension` to your kernel before booting:

```php
<?php

use BackTo\Framework\Bundle\Blocks\BlocksExtension;

$kernel->addExtension(new BlocksExtension());
$kernel->boot();
```

## Step 2: Create a custom block

Extend `CustomBlock` and set the block name:

```php
<?php

declare(strict_types=1);

namespace App\Blocks;

use BackTo\Framework\Bundle\Blocks\CustomBlock;

final class HeroBlock extends CustomBlock
{
    protected string $name = 'my-theme/hero';
}
```

The class is auto-detected via the `wordpress.block` tag and registered on the `init` hook.

## Step 3: Create a dynamic block

A dynamic block renders its HTML on the server. Implement the `DynamicBlock` interface alongside `CustomBlock`:

```php
<?php

declare(strict_types=1);

namespace App\Blocks;

use BackTo\Framework\Bundle\Blocks\CustomBlock;
use BackTo\Framework\Contracts\DynamicBlock;

final class HeroDynamicBlock extends CustomBlock implements DynamicBlock
{
    protected string $name = 'my-theme/hero-dynamic';

    public function renderBlock(array $attributes, string $content): string
    {
        return '<section class="hero">' . esc_html($attributes['title'] ?? '') . '</section>';
    }
}
```

## Step 4: Create a block style

Extend `CustomBlockStyle` to add a visual variant to existing blocks:

```php
<?php

declare(strict_types=1);

namespace App\Blocks;

use BackTo\Framework\Bundle\Blocks\CustomBlockStyle;

final class ShadowStyle extends CustomBlockStyle
{
    protected array $blocks = ['core/group', 'core/columns'];
    protected string $styleName = 'shadow';
    protected string $label = 'Shadow';
}
```

The style is registered automatically for each listed block on the `after_setup_theme` hook.

## Next steps

- See [Common tasks](how-to/README.md) for SVG inlining and multi-block styling
- See [API reference](reference/README.md) for the complete class documentation
