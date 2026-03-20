# Register a custom dynamic block

Extend `CustomBlock` and implement a render callback. The framework registers it automatically via `RegisterBlock`:

```php
<?php

namespace MyPlugin\Blocks;

use BackTo\Framework\Bundle\Blocks\CustomBlock;

final class TestimonialBlock extends CustomBlock
{
    protected string $name = 'my-plugin/testimonial';

    public function getArgs(): array
    {
        return [
            'render_callback' => [$this, 'render'],
            'attributes' => [
                'quote' => ['type' => 'string', 'default' => ''],
                'author' => ['type' => 'string', 'default' => ''],
                'role' => ['type' => 'string', 'default' => ''],
                'rating' => ['type' => 'integer', 'default' => 5],
            ],
        ];
    }

    public function render(array $attributes): string
    {
        $quote = esc_html($attributes['quote']);
        $author = esc_html($attributes['author']);
        $role = esc_html($attributes['role']);
        $stars = str_repeat('★', $attributes['rating']);

        return <<<HTML
        <blockquote class="wp-block-my-plugin-testimonial">
            <p>{$quote}</p>
            <footer>
                <cite>{$author}</cite>
                <span class="role">{$role}</span>
                <span class="rating">{$stars}</span>
            </footer>
        </blockquote>
        HTML;
    }
}
```

Register it as a service:

```php
$services->set(TestimonialBlock::class)
    ->tag('wordpress.block');
```

The `RegisterBlockPass` compiler pass collects all tagged services into the `BlockRegistry`, and `RegisterBlock` registers them on `init`.
