# Control SVG inlining scope


`ReplaceImgBlockBySvgBlock` replaces all `core/image` blocks pointing to SVG files. To limit this to specific contexts, wrap the service with a conditional check:

```php
<?php

namespace MyPlugin\Blocks;

use BackTo\Framework\Assets\ReplaceImgTagBySvgTag;
use BackTo\Framework\Contracts\HookDispatcherInterface;

final class ConditionalSvgInline
{
    public function __construct(
        private readonly ReplaceImgTagBySvgTag $replacer,
        private readonly HookDispatcherInterface $hookDispatcher,
    ) {}

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter(
            'render_block_core/image',
            [$this, 'maybeInlineSvg'],
            10,
            2
        );
    }

    public function maybeInlineSvg(string $blockContent, array $block): string
    {
        // Only inline SVGs that have the "inline-svg" CSS class
        if (!str_contains($blockContent, 'inline-svg')) {
            return $blockContent;
        }

        return $this->replacer->replace($blockContent);
    }
}
```

Then exclude the default `ReplaceImgBlockBySvgBlock` from autoloading and load your conditional version instead.
