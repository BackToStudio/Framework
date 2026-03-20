# Blocks Bundle — How-to guides

*How-to — Task-oriented*

Practical recipes for common block registration tasks.

---

## Replace SVG `<img>` tags with inline SVG

Enable `ReplaceImgBlockBySvgBlock` in your service configuration. It hooks into the `render_block_core/image` filter and replaces `<img>` tags pointing to SVG files with the actual SVG content inline, using `ReplaceImgTagBySvgTag`.

No configuration is needed — the action is self-contained.

---

## Apply a style to multiple blocks at once

List all target blocks in the `$blocks` property of your `CustomBlockStyle`:

```php
<?php

use BackTo\Framework\Bundle\Blocks\CustomBlockStyle;

final class OutlineStyle extends CustomBlockStyle
{
    protected array $blocks = [
        'core/group',
        'core/cover',
        'core/columns',
        'core/image',
    ];
    protected string $styleName = 'outline';
    protected string $label = 'Outline';
}
```

`RegisterBlockStyles` iterates over each block and calls `register_block_style()` for each one.

---

## Avoid duplicate block registration

`RegisterBlock` automatically checks via `BlockRegistrarInterface::exists()` whether a block is already registered in `WP_Block_Type_Registry` before registering it. Duplicates are silently ignored. No action is needed on your part.
