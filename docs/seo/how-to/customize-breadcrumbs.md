# Customize breadcrumbs

Implement `BreadcrumbSchemaGeneratorInterface` to replace the default breadcrumb generation:

```php
<?php

namespace MyTheme\Seo;

use BackTo\Framework\Bundle\Seo\Contracts\BreadcrumbSchemaGeneratorInterface;
use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaType;

final class ShopBreadcrumbGenerator implements BreadcrumbSchemaGeneratorInterface
{
    public function generate(): ?SchemaType
    {
        return Schema::breadcrumbList()->items([
            Schema::listItem()->position(1)->name('Home')->url(home_url('/')),
            Schema::listItem()->position(2)->name('Shop')->url(home_url('/shop/')),
            Schema::listItem()->position(3)->name(get_the_title()),
        ]);
    }
}
```

Register your implementation in the DI container to override the default:

```php
<?php

use BackTo\Framework\Bundle\Seo\Contracts\BreadcrumbSchemaGeneratorInterface;

$containerBuilder->register(BreadcrumbSchemaGeneratorInterface::class, ShopBreadcrumbGenerator::class);
```
