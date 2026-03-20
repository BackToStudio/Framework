# Add LocalBusiness schema for service providers

```php
<?php

use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;

add_action('framework/seo/schema', function (SchemaManager $manager) {
    $business = Schema::type('LocalBusiness')
        ->set('name', 'Acme Plumbing')
        ->set('description', 'Professional plumbing services in Paris.')
        ->set('url', 'https://acme-plumbing.fr')
        ->set('telephone', '+33-1-23-45-67-89')
        ->set('priceRange', '€€')
        ->set('image', 'https://acme-plumbing.fr/images/storefront.jpg')
        ->set('address', Schema::postalAddress()
            ->streetAddress('15 Rue de la Paix')
            ->addressLocality('Paris')
            ->postalCode('75002')
            ->addressCountry('FR')
        )
        ->set('geo', Schema::type('GeoCoordinates')
            ->set('latitude', 48.8698)
            ->set('longitude', 2.3311)
        )
        ->set('openingHoursSpecification', [
            Schema::type('OpeningHoursSpecification')
                ->set('dayOfWeek', ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'])
                ->set('opens', '08:00')
                ->set('closes', '18:00'),
            Schema::type('OpeningHoursSpecification')
                ->set('dayOfWeek', 'Saturday')
                ->set('opens', '09:00')
                ->set('closes', '13:00'),
        ]);

    $manager->add($business);
});
```
