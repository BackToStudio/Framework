# Create a custom cleanup action

Create a class implementing `Hooks` to add project-specific cleanup:

```php
<?php

namespace MyTheme\Actions;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\HookInterface;

final class RemoveJetpackOpenGraph implements HookInterface
{
    public function __construct(
        private readonly HookDispatcherInterface $hookDispatcher,
    ) {}

    public function hooks(): void
    {
        // Remove Jetpack's Open Graph tags (handled by SEO plugin)
        $this->hookDispatcher->addFilter('jetpack_enable_open_graph', '__return_false');

        // Remove Jetpack related posts on single posts
        $this->hookDispatcher->addFilter('jetpack_relatedposts_filter_enabled_for_request', '__return_false');

        // Remove WooCommerce generator tag
        $this->hookDispatcher->removeAction('wp_head', 'wc_generator_tag');
    }
}
```

Place the file in your `Actions/` directory. If you use wildcard autoloading, it will be registered automatically:

```php
$services->load('MyTheme\\Actions\\', 'Actions/*');
```
