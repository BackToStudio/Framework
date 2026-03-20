# Add a schema for a custom post type

Create a generator class and register it in the configuration.

### 1. Create the generator

```php
<?php

namespace MyTheme\Seo;

use BackTo\Framework\Contracts\ContentQueryInterface;
use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaType;

final class EventSchemaGenerator
{
    public function __construct(
        private readonly ContentQueryInterface $contentQuery,
    ) {}

    public function generate(?int $postId = null): ?SchemaType
    {
        $post = $this->contentQuery->getPost($postId);

        if ($post === null) {
            return null;
        }

        return Schema::event()
            ->name($post->post_title)
            ->description($this->contentQuery->getTheExcerpt($post))
            ->startDate(get_post_meta($post->ID, 'event_start', true))
            ->endDate(get_post_meta($post->ID, 'event_end', true))
            ->location(
                Schema::place()
                    ->name(get_post_meta($post->ID, 'event_venue', true))
                    ->address(get_post_meta($post->ID, 'event_address', true))
            )
            ->url($this->contentQuery->getPermalink($post->ID));
    }
}
```

### 2. Register in configuration

```php
<?php

// config/seo.php
return [
    'schema' => [
        'post_type_map' => [
            'post'  => \BackTo\Framework\Bundle\Seo\Schema\Generator\ArticleSchemaGenerator::class,
            'event' => \MyTheme\Seo\EventSchemaGenerator::class,
        ],
    ],
];
```

The `PostTypeSchemaResolver` will automatically use your generator on singular pages of the `event` post type.
