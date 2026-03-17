# Seo

Unified SEO plugin integration. Built-in providers: Yoast SEO, SEOPress.

## Configuration

SEO parameters are managed by `SeoConfiguration` and overridden via the fluent `SeoConfigurator` in `config/seo.php`:

```php
<?php

use BackTo\Framework\Seo\SeoConfigurator;

return static function (SeoConfigurator $seo): void {
    $seo
        ->titleSeparator('-')
        ->robotsDefault('noindex, nofollow');
};
```

| Parameter | Default | Configurator method |
|-----------|---------|---------------------|
| `seo.title_separator` | `\|` | `titleSeparator(string)` |
| `seo.robots_default` | `index, follow` | `robotsDefault(string)` |

## Contracts

### `SeoProviderInterface` extends `SocialLinksProviderInterface`, `MetaProviderInterface`

```php
interface SeoProviderInterface
{
    public function getName(): string;
    public function isActive(): bool;
    // + all social links + meta methods
}
```

### `SocialLinksProviderInterface`

Methods: `getFacebookUrl`, `getTwitterUrl`, `getInstagramUrl`, `getLinkedInUrl`, `getPinterestUrl`, `getYouTubeUrl`, `getSocialLinks`.

### `MetaProviderInterface`

Methods: `getTitle`, `getDescription`, `getCanonicalUrl`, `getOgTitle`, `getOgDescription`, `getOgImageUrl` (all with optional `?int $postId`).

## Schema (Structured Data)

The SEO module includes a full JSON-LD structured data system based on schema.org.

### `Schema` (static factory)

Creates schema.org types via static methods:

```php
use BackTo\Framework\Seo\Schema;

Schema::article();        // Article
Schema::organization();   // Organization
Schema::product();        // Product
Schema::event();          // Event
Schema::faqPage();        // FAQPage
Schema::course();         // Course
Schema::jobPosting();     // JobPosting
Schema::videoObject();    // VideoObject
Schema::localBusiness();  // LocalBusiness
Schema::review();         // Review
Schema::webSite();        // WebSite
Schema::breadcrumbList(); // BreadcrumbList
Schema::person();         // Person
Schema::offer();          // Offer
Schema::type('Custom');   // Any type by name
Schema::ref('#id');       // @id reference (SchemaRef)
```

### `SchemaType` (base class)

Fluent builder for all schema.org types:

```php
$article = Schema::article()
    ->id('https://example.com/post/1#article')
    ->set('headline', 'My Article')
    ->set('author', Schema::person()->set('name', 'John'));
```

| Method | Description |
|--------|-------------|
| `set(string, mixed)` | Set any property |
| `id(string)` | Set the `@id` for graph linking |
| `toArray()` | Export as JSON-LD array |
| `validate()` | Returns missing required properties |
| `isValid()` | Whether all required properties are set |
| `getType()` | Get the `@type` string |

### `SchemaRef` (`@id` linking)

References another schema node by `@id`, avoiding data duplication in a `@graph`:

```php
$org = Schema::organization()->id('https://example.com/#org')->set('name', 'Acme');
$article = Schema::article()->set('publisher', Schema::ref('https://example.com/#org'));
```

Renders as `{"publisher": {"@id": "https://example.com/#org"}}`.

### `SchemaManager`

Central registry that collects schemas and renders a single JSON-LD `<script>` block:

```php
$manager->add(Schema::organization()->set('name', 'Acme'));
$manager->add(Schema::article()->set('headline', 'Hello'));
echo $manager->render(); // <script type="application/ld+json">...</script>
```

Uses `@graph` when multiple schemas are registered. Provides `validate()` to check all schemas at once.

| Method | Description |
|--------|-------------|
| `add(SchemaType)` | Register a schema |
| `render()` | Output JSON-LD `<script>` block |
| `validate()` | Returns `array<type, missing[]>` for all schemas |
| `hasSchemas()` | Whether any schemas are registered |
| `getSchemas()` | Get all registered `SchemaType` instances |
| `toArray()` | Export all schemas as arrays |

### Schema validation

Concrete types define required properties per Google's rich results guidelines. Call `validate()` to get missing properties:

```php
$article = Schema::article()->set('headline', 'Test');
$missing = $article->validate(); // ['author', 'datePublished']
```

Types with validation: `Article`, `Product`, `Event`, `Course`, `JobPosting`, `VideoObject`, `FAQPage`, `Review`, `LocalBusiness`, `Offer`.

### `PostTypeSchemaResolver` (CPT mapping)

Maps WordPress post types to schema generators. Configured via `config/seo.php`:

```php
// config/seo.php
return [
    'schema' => [
        'post_type_map' => [
            'post'      => \App\Seo\ArticleSchemaGenerator::class,
            'formation' => \App\Seo\CourseSchemaGenerator::class,
        ],
        'disable_plugin_schema' => true,
    ],
];
```

Each generator must implement a `generate(?int $postId): ?SchemaType` method.

### `SeoConfig`

Loads `config/seo.php` from the theme directory. Supports dot-notation access:

| Method | Description |
|--------|-------------|
| `get(string $key, mixed $default)` | Dot-notation getter (e.g. `schema.post_type_map`) |
| `getPostTypeMap()` | Returns the CPT → generator mapping |
| `shouldDisablePluginSchema()` | Whether to disable plugin schema output (default: `true`) |
| `all()` | Full config array |
