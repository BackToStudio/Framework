# Schema types

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
