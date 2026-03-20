# Add block supports (colors, spacing, typography)

Use the `supports` key in `block.json` or in the `getArgs()` method:

```php
public function getArgs(): array
{
    return [
        'render_callback' => [$this, 'render'],
        'supports' => [
            'align' => ['wide', 'full'],
            'color' => [
                'background' => true,
                'text' => true,
                'gradients' => true,
            ],
            'spacing' => [
                'margin' => true,
                'padding' => true,
            ],
            'typography' => [
                'fontSize' => true,
                'lineHeight' => true,
            ],
        ],
    ];
}
```

WordPress automatically adds inline styles and wrapper classes. Access them in your render callback via `$attributes['style']` and `$attributes['className']`.
