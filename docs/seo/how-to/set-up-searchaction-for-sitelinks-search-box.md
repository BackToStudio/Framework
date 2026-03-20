# Set up SearchAction for sitelinks search box

Add a `WebSite` schema with a `SearchAction` to enable Google's sitelinks search box:

```php
<?php

use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;

add_action('framework/seo/schema', function (SchemaManager $manager) {
    if (!is_front_page()) {
        return;
    }

    $website = Schema::webSite()
        ->name(get_bloginfo('name'))
        ->url(home_url('/'))
        ->set('potentialAction',
            Schema::type('SearchAction')
                ->set('target', Schema::type('EntryPoint')
                    ->set('urlTemplate', home_url('/?s={search_term_string}')))
                ->set('query-input', 'required name=search_term_string')
        );

    $manager->add($website);
});
```
