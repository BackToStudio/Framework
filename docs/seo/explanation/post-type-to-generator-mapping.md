# Post type to generator mapping

The `PostTypeSchemaResolver` maps post types to schema generators via the `config/seo.php` file:

```php
'post_type_map' => [
    'post'   => ArticleSchemaGenerator::class,
    'event'  => EventSchemaGenerator::class,
    'course' => CourseSchemaGenerator::class,
],
```

Each generator must expose a `generate(?int $postId): ?SchemaType` method. The resolver:

1. Checks that the current page is singular (`is_singular()`)
2. Reads the `post_type` of the current post
3. Looks up a generator in the mapping
4. Calls `generate($postId)` and adds the result to the `SchemaManager`

Generators use duck typing (no interface required) -- any object with a `generate(?int): ?SchemaType` method works. This keeps custom generators simple and decoupled from the framework.
