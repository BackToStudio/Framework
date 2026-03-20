# Enqueue theme CSS and JavaScript with proper dependencies

```php
<?php

namespace MyTheme\Assets;

use BackTo\Framework\Contracts\HookDispatcherInterface;

final class ThemeAssets
{
    public function __construct(
        private readonly HookDispatcherInterface $hookDispatcher,
        private readonly string $themeDirectory,
    ) {}

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('wp_enqueue_scripts', [$this, 'enqueue']);
    }

    public function enqueue(): void
    {
        $themeUri = get_template_directory_uri();

        // Critical CSS loaded in head
        wp_enqueue_style(
            'my-theme-main',
            $themeUri . '/assets/css/main.css',
            [],
            filemtime($this->themeDirectory . '/assets/css/main.css')
        );

        // JS deferred by the Performance bundle automatically
        wp_enqueue_script(
            'my-theme-app',
            $themeUri . '/assets/js/app.js',
            [],
            filemtime($this->themeDirectory . '/assets/js/app.js'),
            true
        );
    }
}
```

The `$themeDirectory` parameter is injected automatically by the kernel via `%themeDirectory%`. The Performance bundle will defer the script automatically unless it is in the exclusion list.
