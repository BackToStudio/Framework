# How to Register Block Styles

## Basic registration

Create a class extending `CustomBlockStyle`:

```php
<?php

namespace MyTheme\Blocks;

use BackTo\Framework\Blocks\CustomBlockStyle;

class ShadowStyle extends CustomBlockStyle
{
    protected array $blocks = ['core/group', 'core/columns'];
    protected string $styleName = 'shadow';
    protected string $label = 'Shadow';
}
```

The framework auto-discovers any class implementing `BlockStyleInterface`, tags it with `wordpress.block_style`, and registers each style for every block in the `$blocks` array at the `after_setup_theme` hook.

## Multiple styles

Create one class per style variation:

```php
class RoundedStyle extends CustomBlockStyle
{
    protected array $blocks = ['core/image', 'core/group'];
    protected string $styleName = 'rounded';
    protected string $label = 'Rounded';
}

class OutlineStyle extends CustomBlockStyle
{
    protected array $blocks = ['core/button'];
    protected string $styleName = 'outline';
    protected string $label = 'Outline';
}
```

## Custom implementation

If you need more control, implement `BlockStyleInterface` directly:

```php
<?php

namespace MyTheme\Blocks;

use BackTo\Framework\Contracts\BlockStyleInterface;

class DynamicStyle implements BlockStyleInterface
{
    public function getStyleName(): string
    {
        return 'dynamic';
    }

    public function getLabel(): string
    {
        return __('Dynamic', 'my-theme');
    }

    public function getBlocks(): array
    {
        return ['core/paragraph', 'core/heading'];
    }

    public function getProperties(): array
    {
        return [
            'name' => $this->getStyleName(),
            'label' => $this->getLabel(),
        ];
    }
}
```
