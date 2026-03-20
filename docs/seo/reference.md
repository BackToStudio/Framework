# SEO Bundle -- API Reference

*Reference -- Information-oriented*

---

## `Schema` (static factory)

**Namespace:** `BackTo\Framework\Bundle\Seo`

Final class providing static methods to instantiate each schema.org type.

| Method | Return | Description |
|---|---|---|
| `organization()` | `Organization` | Organization / company |
| `localBusiness()` | `LocalBusiness` | Local business |
| `article()` | `Article` | Blog / news article |
| `breadcrumbList()` | `BreadcrumbList` | Breadcrumb trail |
| `listItem()` | `ListItem` | Breadcrumb list element |
| `person()` | `Person` | Person |
| `postalAddress()` | `PostalAddress` | Postal address |
| `webSite()` | `WebSite` | Website |
| `imageObject()` | `ImageObject` | Image |
| `searchAction()` | `SearchAction` | Search action |
| `event()` | `Event` | Event |
| `course()` | `Course` | Course / training |
| `courseInstance()` | `CourseInstance` | Course session |
| `offer()` | `Offer` | Commercial offer |
| `place()` | `Place` | Physical location |
| `virtualLocation()` | `VirtualLocation` | Virtual location (URL) |
| `geoCoordinates()` | `GeoCoordinates` | GPS coordinates |
| `aggregateRating()` | `AggregateRating` | Aggregate rating |
| `product()` | `Product` | Product |
| `brand()` | `Brand` | Brand |
| `offerShippingDetails()` | `OfferShippingDetails` | Shipping details |
| `merchantReturnPolicy()` | `MerchantReturnPolicy` | Return policy |
| `monetaryAmount()` | `MonetaryAmount` | Monetary amount |
| `review()` | `Review` | Review |
| `rating()` | `Rating` | Rating |
| `videoObject()` | `VideoObject` | Video |
| `clip()` | `Clip` | Video clip (key moment) |
| `faqPage()` | `FAQPage` | FAQ page |
| `question()` | `Question` | Question (FAQ) |
| `answer()` | `Answer` | Answer (FAQ) |
| `jobPosting()` | `JobPosting` | Job posting |
| `type(string $type)` | `SchemaType` | Generic type by name |
| `ref(string $id)` | `SchemaRef` | `@id` reference to another node |

---

## `SchemaType`

**Namespace:** `BackTo\Framework\Bundle\Seo\Schema`
**Implements:** `JsonSerializable`

Base class for all schema.org types.

| Method | Signature | Description |
|---|---|---|
| `set` | `(string $property, mixed $value): static` | Set any property (fluent) |
| `id` | `(string $id): static` | Set the `@id` |
| `getType` | `(): string` | Return the `@type` |
| `getProperties` | `(): array<string, mixed>` | Return all properties |
| `toArray` | `(): array<string, mixed>` | Convert to JSON-LD array |
| `validate` | `(): string[]` | Return missing required properties |
| `isValid` | `(): bool` | `true` if all required properties are present |

---

## `SchemaRef`

**Namespace:** `BackTo\Framework\Bundle\Seo\Schema`
**Implements:** `JsonSerializable`

Reference to another node in the JSON-LD graph by `@id`.

| Method | Signature | Description |
|---|---|---|
| `getId` | `(): string` | Return the identifier |
| `toArray` | `(): array{@id: string}` | `['@id' => '...']` |

---

## `SchemaManager`

**Namespace:** `BackTo\Framework\Bundle\Seo\Schema`

Central registry for structured data. Collects `SchemaType` instances and renders them as JSON-LD.

| Method | Signature | Description |
|---|---|---|
| `add` | `(SchemaType $schema): self` | Add a schema to the registry |
| `hasSchemas` | `(): bool` | Whether any schemas are registered |
| `getSchemas` | `(): SchemaType[]` | Return all registered schemas |
| `validate` | `(): array<string, string[]>` | Validate all schemas, return errors by type |
| `toArray` | `(): array<int, array>` | Export as array of JSON-LD data |
| `render` | `(): string` | Render a `<script type="application/ld+json">` block |

Render behavior: 0 schemas returns empty string, 1 schema returns a single JSON-LD object with `@context`, 2+ schemas returns an object with `@context` and `@graph`.

---

## Schema types

All types extend `SchemaType` and live in `BackTo\Framework\Bundle\Seo\Schema\Type\`.

### `AggregateRating`

| Method | Signature |
|---|---|
| `ratingValue` | `(float\|string $value): static` |
| `bestRating` | `(float\|string $value): static` |
| `worstRating` | `(float\|string $value): static` |
| `ratingCount` | `(int $count): static` |
| `reviewCount` | `(int $count): static` |

### `Answer`

| Method | Signature |
|---|---|
| `text` | `(string $text): static` |

### `Article`

Required: `headline`, `author`, `datePublished`

| Method | Signature |
|---|---|
| `headline` | `(string $headline): static` |
| `description` | `(string $description): static` |
| `author` | `(SchemaType\|string $author): static` |
| `publisher` | `(SchemaType $publisher): static` |
| `datePublished` | `(string $date): static` |
| `dateModified` | `(string $date): static` |
| `image` | `(string\|SchemaType $image): static` |
| `url` | `(string $url): static` |
| `mainEntityOfPage` | `(string\|SchemaType $page): static` |
| `articleSection` | `(string $section): static` |
| `keywords` | `(array $keywords): static` |
| `wordCount` | `(int $count): static` |

### `Brand`

| Method | Signature |
|---|---|
| `name` | `(string $name): static` |
| `url` | `(string $url): static` |
| `logo` | `(string\|SchemaType $logo): static` |

### `BreadcrumbList`

| Method | Signature |
|---|---|
| `items` | `(SchemaType[] $items): static` |

Sets `itemListElement` from an array of `ListItem` instances.

### `Clip`

| Method | Signature |
|---|---|
| `name` | `(string $name): static` |
| `startOffset` | `(int $seconds): static` |
| `endOffset` | `(int $seconds): static` |
| `url` | `(string $url): static` |

### `Course`

Required: `name`, `description`, `provider`

| Method | Signature |
|---|---|
| `name` | `(string $name): static` |
| `description` | `(string $description): static` |
| `provider` | `(SchemaType $provider): static` |
| `url` | `(string $url): static` |
| `image` | `(string\|SchemaType $image): static` |
| `inLanguage` | `(string $language): static` |
| `hasCourseInstance` | `(SchemaType\|array $instances): static` |
| `offers` | `(SchemaType\|array $offers): static` |
| `courseCode` | `(string $code): static` |
| `educationalLevel` | `(string $level): static` |
| `coursePrerequisites` | `(array $prerequisites): static` |
| `numberOfCredits` | `(int $credits): static` |
| `timeRequired` | `(string $duration): static` |
| `aggregateRating` | `(SchemaType $rating): static` |

### `CourseInstance`

| Method | Signature |
|---|---|
| `courseMode` | `(string $mode): static` |
| `startDate` | `(string $date): static` |
| `endDate` | `(string $date): static` |
| `location` | `(SchemaType\|string $location): static` |
| `instructor` | `(SchemaType $instructor): static` |
| `inLanguage` | `(string $language): static` |
| `offers` | `(SchemaType\|array $offers): static` |
| `courseSchedule` | `(SchemaType $schedule): static` |

### `Event`

Required: `name`, `startDate`, `location`

| Method | Signature |
|---|---|
| `name` | `(string $name): static` |
| `description` | `(string $description): static` |
| `startDate` | `(string $date): static` |
| `endDate` | `(string $date): static` |
| `location` | `(SchemaType\|string $location): static` |
| `organizer` | `(SchemaType $organizer): static` |
| `performer` | `(SchemaType $performer): static` |
| `image` | `(string\|SchemaType $image): static` |
| `url` | `(string $url): static` |
| `offers` | `(SchemaType\|array $offers): static` |
| `eventStatus` | `(string $status): static` |
| `eventAttendanceMode` | `(string $mode): static` |
| `previousStartDate` | `(string $date): static` |
| `doorTime` | `(string $time): static` |
| `inLanguage` | `(string $language): static` |
| `isAccessibleForFree` | `(bool $free): static` |

### `FAQPage`

Required: `mainEntity`

| Method | Signature |
|---|---|
| `mainEntity` | `(SchemaType[] $questions): static` |

### `GeoCoordinates`

| Method | Signature |
|---|---|
| `latitude` | `(float $latitude): static` |
| `longitude` | `(float $longitude): static` |

### `ImageObject`

| Method | Signature |
|---|---|
| `url` | `(string $url): static` |
| `width` | `(int $width): static` |
| `height` | `(int $height): static` |
| `caption` | `(string $caption): static` |

### `JobPosting`

Required: `title`, `description`, `datePosted`, `hiringOrganization`

| Method | Signature |
|---|---|
| `title` | `(string $title): static` |
| `description` | `(string $description): static` |
| `datePosted` | `(string $date): static` |
| `validThrough` | `(string $date): static` |
| `hiringOrganization` | `(SchemaType $organization): static` |
| `jobLocation` | `(SchemaType $location): static` |
| `baseSalary` | `(SchemaType $salary): static` |
| `employmentType` | `(string\|array $type): static` |
| `jobLocationType` | `(string $type): static` |
| `applicantLocationRequirements` | `(SchemaType $requirements): static` |
| `url` | `(string $url): static` |
| `identifier` | `(SchemaType\|string $identifier): static` |
| `directApply` | `(bool $direct): static` |
| `industry` | `(string $industry): static` |
| `qualifications` | `(string $qualifications): static` |
| `responsibilities` | `(string $responsibilities): static` |
| `skills` | `(string $skills): static` |
| `experienceRequirements` | `(string\|SchemaType $requirements): static` |
| `educationRequirements` | `(string\|SchemaType $requirements): static` |

### `ListItem`

| Method | Signature |
|---|---|
| `position` | `(int $position): static` |
| `name` | `(string $name): static` |
| `url` | `(string $url): static` |

Note: `url()` sets the `item` property in JSON-LD output.

### `LocalBusiness`

Required: `name`, `address`

| Method | Signature |
|---|---|
| `name` | `(string $name): static` |
| `url` | `(string $url): static` |
| `logo` | `(string\|SchemaType $logo): static` |
| `image` | `(string\|SchemaType $image): static` |
| `description` | `(string $description): static` |
| `telephone` | `(string $telephone): static` |
| `email` | `(string $email): static` |
| `address` | `(SchemaType $address): static` |
| `priceRange` | `(string $priceRange): static` |
| `openingHours` | `(string $openingHours): static` |
| `geo` | `(SchemaType $geo): static` |
| `sameAs` | `(array $urls): static` |

### `MerchantReturnPolicy`

| Method | Signature |
|---|---|
| `applicableCountry` | `(string $country): static` |
| `returnPolicyCategory` | `(string $category): static` |
| `merchantReturnDays` | `(int $days): static` |
| `returnMethod` | `(string $method): static` |
| `returnFees` | `(string $fees): static` |

### `MonetaryAmount`

| Method | Signature |
|---|---|
| `currency` | `(string $currency): static` |
| `value` | `(float\|int\|SchemaType $value): static` |
| `minValue` | `(float\|int $value): static` |
| `maxValue` | `(float\|int $value): static` |

### `Offer`

Required: `price`, `priceCurrency`

| Method | Signature |
|---|---|
| `price` | `(string\|float\|int $price): static` |
| `priceCurrency` | `(string $currency): static` |
| `url` | `(string $url): static` |
| `availability` | `(string $availability): static` |
| `validFrom` | `(string $date): static` |
| `validThrough` | `(string $date): static` |
| `category` | `(string $category): static` |

### `OfferShippingDetails`

| Method | Signature |
|---|---|
| `shippingRate` | `(SchemaType $rate): static` |
| `shippingDestination` | `(SchemaType $destination): static` |
| `deliveryTime` | `(SchemaType $time): static` |

### `Organization`

| Method | Signature |
|---|---|
| `name` | `(string $name): static` |
| `url` | `(string $url): static` |
| `logo` | `(string\|SchemaType $logo): static` |
| `sameAs` | `(array $urls): static` |
| `description` | `(string $description): static` |
| `email` | `(string $email): static` |
| `telephone` | `(string $telephone): static` |
| `address` | `(SchemaType $address): static` |

### `Person`

| Method | Signature |
|---|---|
| `name` | `(string $name): static` |
| `url` | `(string $url): static` |
| `email` | `(string $email): static` |
| `image` | `(string\|SchemaType $image): static` |
| `sameAs` | `(array $urls): static` |

### `Place`

| Method | Signature |
|---|---|
| `name` | `(string $name): static` |
| `address` | `(SchemaType\|string $address): static` |
| `geo` | `(SchemaType $geo): static` |
| `url` | `(string $url): static` |

### `PostalAddress`

| Method | Signature |
|---|---|
| `streetAddress` | `(string $street): static` |
| `addressLocality` | `(string $locality): static` |
| `addressRegion` | `(string $region): static` |
| `postalCode` | `(string $postalCode): static` |
| `addressCountry` | `(string $country): static` |

### `Product`

Required: `name`, `image`, `offers`

| Method | Signature |
|---|---|
| `name` | `(string $name): static` |
| `description` | `(string $description): static` |
| `image` | `(string\|SchemaType\|array $image): static` |
| `url` | `(string $url): static` |
| `sku` | `(string $sku): static` |
| `gtin` | `(string $gtin): static` |
| `gtin13` | `(string $gtin13): static` |
| `mpn` | `(string $mpn): static` |
| `brand` | `(SchemaType $brand): static` |
| `color` | `(string $color): static` |
| `size` | `(string $size): static` |
| `material` | `(string $material): static` |
| `offers` | `(SchemaType\|array $offers): static` |
| `aggregateRating` | `(SchemaType $rating): static` |
| `review` | `(SchemaType\|array $reviews): static` |
| `category` | `(string $category): static` |

### `Question`

| Method | Signature |
|---|---|
| `name` | `(string $question): static` |
| `acceptedAnswer` | `(SchemaType $answer): static` |
| `suggestedAnswer` | `(array $answers): static` |

### `Rating`

| Method | Signature |
|---|---|
| `ratingValue` | `(float\|string $value): static` |
| `bestRating` | `(float\|string $value): static` |
| `worstRating` | `(float\|string $value): static` |

### `Review`

Required: `itemReviewed`, `reviewRating`, `author`

| Method | Signature |
|---|---|
| `itemReviewed` | `(SchemaType $item): static` |
| `reviewRating` | `(SchemaType $rating): static` |
| `author` | `(SchemaType\|string $author): static` |
| `datePublished` | `(string $date): static` |
| `reviewBody` | `(string $body): static` |
| `name` | `(string $name): static` |
| `publisher` | `(SchemaType $publisher): static` |

### `SearchAction`

| Method | Signature |
|---|---|
| `target` | `(string $urlTemplate): static` |
| `queryInput` | `(string $input): static` |

### `VideoObject`

Required: `name`, `thumbnailUrl`, `uploadDate`

| Method | Signature |
|---|---|
| `name` | `(string $name): static` |
| `description` | `(string $description): static` |
| `thumbnailUrl` | `(string\|array $url): static` |
| `uploadDate` | `(string $date): static` |
| `duration` | `(string $duration): static` |
| `contentUrl` | `(string $url): static` |
| `embedUrl` | `(string $url): static` |
| `expires` | `(string $date): static` |
| `interactionStatistic` | `(SchemaType $stat): static` |
| `hasPart` | `(SchemaType\|array $parts): static` |
| `publication` | `(SchemaType $event): static` |
| `regionsAllowed` | `(string\|array $regions): static` |

### `VirtualLocation`

| Method | Signature |
|---|---|
| `url` | `(string $url): static` |

### `WebSite`

| Method | Signature |
|---|---|
| `name` | `(string $name): static` |
| `url` | `(string $url): static` |
| `description` | `(string $description): static` |
| `publisher` | `(SchemaType $publisher): static` |
| `inLanguage` | `(string $language): static` |
| `potentialAction` | `(SchemaType $action): static` |

---

## Interfaces

### `SeoProviderInterface`

**Namespace:** `BackTo\Framework\Bundle\Seo\Contracts`
**Extends:** `SocialLinksProviderInterface`, `MetaProviderInterface`

| Method | Signature | Description |
|---|---|---|
| `getName` | `(): string` | Plugin identifier (`'yoast'`, `'seopress'`) |
| `isActive` | `(): bool` | Whether the SEO plugin is active |

### `MetaProviderInterface`

**Namespace:** `BackTo\Framework\Bundle\Seo\Contracts`

| Method | Signature |
|---|---|
| `getTitle` | `(?int $postId = null): ?string` |
| `getDescription` | `(?int $postId = null): ?string` |
| `getCanonicalUrl` | `(?int $postId = null): ?string` |
| `getOgTitle` | `(?int $postId = null): ?string` |
| `getOgDescription` | `(?int $postId = null): ?string` |
| `getOgImageUrl` | `(?int $postId = null): ?string` |

### `SocialLinksProviderInterface`

**Namespace:** `BackTo\Framework\Bundle\Seo\Contracts`

| Method | Signature |
|---|---|
| `getFacebookUrl` | `(): ?string` |
| `getTwitterUrl` | `(): ?string` |
| `getInstagramUrl` | `(): ?string` |
| `getLinkedInUrl` | `(): ?string` |
| `getPinterestUrl` | `(): ?string` |
| `getYouTubeUrl` | `(): ?string` |
| `getSocialLinks` | `(): array<string, string\|null>` |

### `SchemaProviderInterface`

**Namespace:** `BackTo\Framework\Bundle\Seo\Contracts`

| Method | Signature |
|---|---|
| `registerSchemas` | `(SchemaManager $schemaManager): void` |

### `BreadcrumbSchemaGeneratorInterface`

**Namespace:** `BackTo\Framework\Bundle\Seo\Contracts`

| Method | Signature |
|---|---|
| `generate` | `(): ?SchemaType` |

---

## Services

### `SeoManager`

**Namespace:** `BackTo\Framework\Bundle\Seo`

Resolves the active SEO provider from registered providers.

| Method | Signature | Description |
|---|---|---|
| `addProvider` | `(SeoProviderInterface $provider): self` | Add a provider |
| `getProvider` | `(): ?SeoProviderInterface` | Return the first active provider |
| `hasProvider` | `(): bool` | Whether an SEO plugin is active |
| `getSocialLinks` | `(): array<string, string\|null>` | Social links from the active provider |

### `SeoConfig`

**Namespace:** `BackTo\Framework\Bundle\Seo`

Loads configuration from the theme's `config/seo.php`.

| Method | Signature | Description |
|---|---|---|
| `all` | `(): array` | Full configuration array |
| `get` | `(string $key, mixed $default = null): mixed` | Value by dot notation |
| `getPostTypeMap` | `(): array<string, class-string>` | Post type to generator mapping |
| `shouldDisablePluginSchema` | `(): bool` | Whether to disable plugin schema (default: `true`) |

### `SeoConfigurator`

**Namespace:** `BackTo\Framework\Bundle\Seo`

Fluent configurator for SEO container parameters.

| Method | Signature | Description |
|---|---|---|
| `titleSeparator` | `(string $separator): self` | Title separator (default: `\|`) |
| `robotsDefault` | `(string $robots): self` | Default robots directive (default: `index, follow`) |
| `toParameters` | `(): array<string, mixed>` | Export as container parameters |

### `PostTypeSchemaResolver`

**Namespace:** `BackTo\Framework\Bundle\Seo\Schema\Generator`

Resolves a schema generator for a given post type based on the `schema.post_type_map` configuration.

| Method | Signature | Description |
|---|---|---|
| `addGenerator` | `(string $postType, object $generator): self` | Register a generator |
| `supports` | `(string $postType): bool` | Whether a generator exists |
| `resolve` | `(string $postType, ?int $postId = null): ?SchemaType` | Generate the schema |
| `getGenerators` | `(): array<string, object>` | All registered generators |

---

## Providers

### `YoastProvider`

**Namespace:** `BackTo\Framework\Bundle\Seo\Provider`
**Implements:** `SeoProviderInterface`

Detects `wordpress-seo/wp-seo.php`. Reads social options from `wpseo_social` and meta from `_yoast_wpseo_*` post meta keys.

### `SeoPressProvider`

**Namespace:** `BackTo\Framework\Bundle\Seo\Provider`
**Implements:** `SeoProviderInterface`

Detects `wp-seopress/seopress.php`. Reads social options from `seopress_social_option_name` and meta from `_seopress_*` post meta keys.

---

## Hooks

### WordPress hooks registered

| Class | Hook | Priority | Description |
|---|---|---|---|
| `RegisterDefaultSchemas` | `wp` | default | Registers WebSite, Organization, Article, BreadcrumbList |
| `InjectSchemaInHead` | `wp_head` | 1 | Injects JSON-LD into `<head>` |
| `AddSchemaToTimberContext` | `timber/context` | default | Adds `schema` (SchemaManager) to Twig context |
| `AddSocialLinksToTimberContext` | `timber/context` | default | Adds `facebook`, `twitter`, etc. to Twig context |
| `CleanYoastFootprint` | `wpseo_debug_markers`, `wpseo_hide_version` | default | Removes Yoast debug markers |
| `DisablePluginSchema` | `wpseo_json_ld_output` or `seopress_schemas_auto_enabled` | default | Disables plugin schema output |

### Custom action

| Action | Parameters | Description |
|---|---|---|
| `framework/seo/schema` | `(SchemaManager $manager)` | Fired after default schemas are registered. Add custom schemas here. |
