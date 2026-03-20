# Apply a style to multiple blocks at once

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
