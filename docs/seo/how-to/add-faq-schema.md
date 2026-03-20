# Add FAQ schema

```php
<?php

use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;

add_action('framework/seo/schema', function (SchemaManager $manager) {
    $faq = Schema::faqPage()->mainEntity([
        Schema::question()
            ->name('What are the shipping times?')
            ->acceptedAnswer(
                Schema::answer()->text('Standard shipping takes 3-5 business days.')
            ),
        Schema::question()
            ->name('Can I return a product?')
            ->acceptedAnswer(
                Schema::answer()->text('Yes, you have 30 days to return items in original packaging.')
            ),
    ]);

    $manager->add($faq);
});
```

### Dynamic FAQ from ACF fields

```php
<?php

use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;

add_action('framework/seo/schema', function (SchemaManager $manager) {
    $items = get_field('faq_items', get_the_ID());

    if (empty($items)) {
        return;
    }

    $questions = array_map(fn (array $item) =>
        Schema::question()
            ->name($item['question'])
            ->acceptedAnswer(Schema::answer()->text($item['answer'])),
        $items
    );

    $manager->add(Schema::faqPage()->mainEntity($questions));
});
```
