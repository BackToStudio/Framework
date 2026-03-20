# Use a generic schema type

For schema.org types that do not have a dedicated class, use `Schema::type()`:

```php
<?php

use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;

add_action('framework/seo/schema', function (SchemaManager $manager) {
    $recipe = Schema::type('Recipe')
        ->set('name', 'Apple Pie')
        ->set('author', Schema::person()->name('Chef Pierre'))
        ->set('prepTime', 'PT30M')
        ->set('cookTime', 'PT45M')
        ->set('recipeYield', '8 servings')
        ->set('recipeIngredient', ['6 apples', '200g sugar', '1 pie crust']);

    $manager->add($recipe);
});
```
