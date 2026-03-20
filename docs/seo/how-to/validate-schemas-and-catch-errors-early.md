# Validate schemas and catch errors early


Use the `validate()` method to check schemas against Google's required properties:

```php
<?php

use BackTo\Framework\Bundle\Seo\Schema;

$product = Schema::product()
    ->name('Widget')
    ->offers([]);

$errors = $product->validate();
// ['Product requires at least one Offer in "offers"']

if (!empty($errors)) {
    error_log('Schema validation failed: ' . implode(', ', $errors));
}
```

### Validation in a CI/CD pipeline

```php
// tests/SchemaValidationTest.php
public function testProductSchemaIsValid(): void
{
    $generator = $this->container->get(WooCommerceProductSchemaGenerator::class);
    $schema = $generator->generate($this->testProductId);

    $this->assertNotNull($schema);
    $this->assertEmpty($schema->validate(), 'Schema has validation errors');
}
```
