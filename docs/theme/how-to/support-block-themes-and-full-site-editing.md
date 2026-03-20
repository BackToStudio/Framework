# Support block themes and full site editing

For block themes using `theme.json`, ensure the kernel boots early enough for FSE compatibility:

```php
<?php
// functions.php

use BackTo\Framework\Bundle\Theme\ThemeKernel;

$kernel = new ThemeKernel(
    environment: wp_get_environment_type(),
    debug: WP_DEBUG,
);

$kernel->boot(
    directory: get_template_directory(),
    textDomain: 'my-block-theme',
);

// Register block template part areas
add_action('init', function () {
    register_block_pattern_category('my-theme', [
        'label' => 'My Theme Patterns',
    ]);
});
```

The Theme bundle cleanup actions work with both classic and block themes. `RemoveNavigationFallback` is especially useful for block themes to prevent unwanted navigation rendering.
