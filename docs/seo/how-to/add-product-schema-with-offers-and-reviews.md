# Add Product schema with offers and reviews

```php
<?php

use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;

add_action('framework/seo/schema', function (SchemaManager $manager) {
    $product = Schema::product()
        ->name('Wireless Headphones')
        ->description('Noise-cancelling wireless headphones with 30-hour battery life.')
        ->image('https://example.com/images/headphones.jpg')
        ->sku('WH-PRO-001')
        ->gtin13('3614271234567')
        ->brand(Schema::brand()->name('AudioTech'))
        ->offers([
            Schema::offer()
                ->price(199.99)
                ->priceCurrency('USD')
                ->availability('https://schema.org/InStock')
                ->url('https://example.com/shop/headphones'),
        ])
        ->aggregateRating(
            Schema::aggregateRating()
                ->ratingValue(4.7)
                ->bestRating(5)
                ->reviewCount(128)
        )
        ->review([
            Schema::review()
                ->author(Schema::person()->name('Alice Smith'))
                ->reviewRating(Schema::rating()->ratingValue(5)->bestRating(5))
                ->reviewBody('Excellent sound quality.')
                ->datePublished('2025-03-10')
                ->itemReviewed(Schema::type('Product')->set('name', 'Wireless Headphones')),
        ]);

    $manager->add($product);
});
```
