# Create block variations (presets)

Block variations offer preconfigured versions of existing blocks. Register them in JavaScript:

```js
// assets/js/block-variations.js
wp.blocks.registerBlockVariation('core/group', {
    name: 'card',
    title: 'Card',
    description: 'A bordered card with padding',
    attributes: {
        className: 'is-style-card',
        layout: { type: 'constrained' },
    },
    innerBlocks: [
        ['core/heading', { level: 3, placeholder: 'Card title' }],
        ['core/paragraph', { placeholder: 'Card content...' }],
    ],
    scope: ['inserter'],
    icon: 'index-card',
});
```

Enqueue the script on the block editor:

```php
<?php

namespace MyPlugin\Blocks;

use BackTo\Framework\Contracts\HookDispatcherInterface;

final class RegisterBlockVariations
{
    public function __construct(
        private readonly HookDispatcherInterface $hookDispatcher,
        private readonly string $pluginDirectory,
    ) {}

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('enqueue_block_editor_assets', [$this, 'enqueue']);
    }

    public function enqueue(): void
    {
        wp_enqueue_script(
            'my-plugin-block-variations',
            plugins_url('assets/js/block-variations.js', $this->pluginDirectory . '/plugin.php'),
            ['wp-blocks', 'wp-dom-ready'],
            filemtime($this->pluginDirectory . '/assets/js/block-variations.js'),
            true
        );
    }
}
```
