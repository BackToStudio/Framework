# Add MerchantReturnPolicy to product schema

```php
<?php

use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;

add_action('framework/seo/schema', function (SchemaManager $manager) {
    $product = Schema::product()
        ->name('Premium Widget')
        ->offers([
            Schema::offer()
                ->price(49.99)
                ->priceCurrency('EUR')
                ->availability('https://schema.org/InStock')
                ->hasMerchantReturnPolicy(
                    Schema::type('MerchantReturnPolicy')
                        ->set('applicableCountry', 'FR')
                        ->set('returnPolicyCategory', 'https://schema.org/MerchantReturnFiniteReturnWindow')
                        ->set('merchantReturnDays', 30)
                        ->set('returnMethod', 'https://schema.org/ReturnByMail')
                        ->set('returnFees', 'https://schema.org/FreeReturn')
                )
                ->set('shippingDetails',
                    Schema::type('OfferShippingDetails')
                        ->set('shippingRate', Schema::type('MonetaryAmount')
                            ->set('value', 5.99)
                            ->set('currency', 'EUR'))
                        ->set('deliveryTime', Schema::type('ShippingDeliveryTime')
                            ->set('handlingTime', Schema::type('QuantitativeValue')
                                ->set('minValue', 0)->set('maxValue', 1)->set('unitCode', 'DAY'))
                            ->set('transitTime', Schema::type('QuantitativeValue')
                                ->set('minValue', 2)->set('maxValue', 5)->set('unitCode', 'DAY'))
                        )
                        ->set('shippingDestination', Schema::type('DefinedRegion')
                            ->set('addressCountry', 'FR'))
                ),
        ]);

    $manager->add($product);
});
```
