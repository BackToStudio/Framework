# Add theme support declarations


```php
<?php

namespace MyTheme;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\HookInterface;

final class ThemeSupport implements HookInterface
{
    public function __construct(
        private readonly HookDispatcherInterface $hookDispatcher,
    ) {}

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('after_setup_theme', [$this, 'setup']);
    }

    public function setup(): void
    {
        add_theme_support('title-tag');
        add_theme_support('post-thumbnails');
        add_theme_support('html5', ['search-form', 'comment-form', 'comment-list', 'gallery', 'caption']);
        add_theme_support('responsive-embeds');
        add_theme_support('editor-styles');
        add_theme_support('wp-block-styles');
        add_theme_support('align-wide');

        register_nav_menus([
            'primary'   => __('Primary Menu', 'my-theme'),
            'footer'    => __('Footer Menu', 'my-theme'),
        ]);

        set_post_thumbnail_size(1200, 630, true);
        add_image_size('card', 600, 400, true);
    }
}
```
