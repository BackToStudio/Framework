# Add a JobPosting schema

```php
<?php

use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;

add_action('framework/seo/schema', function (SchemaManager $manager) {
    $job = Schema::jobPosting()
        ->title('Senior PHP Developer')
        ->description('We are looking for an experienced PHP developer...')
        ->datePosted('2025-03-01')
        ->validThrough('2025-06-01')
        ->employmentType('FULL_TIME')
        ->hiringOrganization(
            Schema::organization()
                ->name('Acme Corp')
                ->url('https://acme.com')
                ->logo('https://acme.com/logo.png')
        )
        ->jobLocation(
            Schema::place()->address(
                Schema::postalAddress()
                    ->streetAddress('123 Main Street')
                    ->addressLocality('New York')
                    ->postalCode('10001')
                    ->addressCountry('US')
            )
        )
        ->baseSalary(
            Schema::monetaryAmount()
                ->currency('USD')
                ->minValue(80000)
                ->maxValue(120000)
        );

    $manager->add($job);
});
```
