# Generate dynamic Product schema from WooCommerce data

Pull product data directly from WooCommerce instead of hardcoding values:

```php
<?php

namespace MyTheme\Seo;

use BackTo\Framework\Contracts\ContentQueryInterface;
use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaType;

final class WooCommerceProductSchemaGenerator
{
    public function __construct(
        private readonly ContentQueryInterface $contentQuery,
    ) {}

    public function generate(?int $postId = null): ?SchemaType
    {
        $post = $this->contentQuery->getPost($postId);
        if ($post === null || $post->post_type !== 'product') {
            return null;
        }

        $product = wc_get_product($post->ID);
        if (!$product) {
            return null;
        }

        $schema = Schema::product()
            ->name($product->get_name())
            ->description($product->get_short_description())
            ->sku($product->get_sku())
            ->image(wp_get_attachment_url($product->get_image_id()))
            ->url($this->contentQuery->getPermalink($post->ID));

        // Offers (supports simple and variable products)
        $offers = [];
        if ($product->is_type('variable')) {
            foreach ($product->get_available_variations() as $variation) {
                $offers[] = Schema::offer()
                    ->price($variation['display_price'])
                    ->priceCurrency(get_woocommerce_currency())
                    ->availability($variation['is_in_stock']
                        ? 'https://schema.org/InStock'
                        : 'https://schema.org/OutOfStock'
                    );
            }
        } else {
            $offers[] = Schema::offer()
                ->price($product->get_price())
                ->priceCurrency(get_woocommerce_currency())
                ->availability($product->is_in_stock()
                    ? 'https://schema.org/InStock'
                    : 'https://schema.org/OutOfStock'
                );
        }
        $schema->offers($offers);

        // Aggregate rating from reviews
        if ($product->get_review_count() > 0) {
            $schema->aggregateRating(
                Schema::aggregateRating()
                    ->ratingValue($product->get_average_rating())
                    ->bestRating(5)
                    ->reviewCount($product->get_review_count())
            );
        }

        // Brand from product attribute or taxonomy
        $brand = $product->get_attribute('brand');
        if ($brand) {
            $schema->brand(Schema::brand()->name($brand));
        }

        return $schema;
    }
}
```

Register it in the post type map:

```php
return [
    'schema' => [
        'post_type_map' => [
            'product' => \MyTheme\Seo\WooCommerceProductSchemaGenerator::class,
        ],
    ],
];
```
