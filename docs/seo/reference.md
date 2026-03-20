# Reference API du bundle SEO

Reference technique complete du bundle SEO : factory, types, manager, interfaces, generateurs et hooks.

**Namespace** : `BackTo\Framework\Bundle\Seo`

---

## Schema (factory statique)

`BackTo\Framework\Bundle\Seo\Schema`

Classe `final` fournissant des methodes statiques pour instancier chaque type Schema.org.

| Methode | Retour | Description |
|---|---|---|
| `organization()` | `Organization` | Organisation / entreprise |
| `localBusiness()` | `LocalBusiness` | Commerce local |
| `article()` | `Article` | Article de blog / presse |
| `breadcrumbList()` | `BreadcrumbList` | Fil d'Ariane |
| `listItem()` | `ListItem` | Element de liste (breadcrumb) |
| `person()` | `Person` | Personne physique |
| `postalAddress()` | `PostalAddress` | Adresse postale |
| `webSite()` | `WebSite` | Site web |
| `imageObject()` | `ImageObject` | Image |
| `searchAction()` | `SearchAction` | Action de recherche |
| `event()` | `Event` | Evenement |
| `course()` | `Course` | Formation / cours |
| `courseInstance()` | `CourseInstance` | Session de formation |
| `offer()` | `Offer` | Offre commerciale |
| `place()` | `Place` | Lieu physique |
| `virtualLocation()` | `VirtualLocation` | Lieu virtuel (URL) |
| `geoCoordinates()` | `GeoCoordinates` | Coordonnees GPS |
| `aggregateRating()` | `AggregateRating` | Note agregee |
| `product()` | `Product` | Produit |
| `brand()` | `Brand` | Marque |
| `offerShippingDetails()` | `OfferShippingDetails` | Details de livraison |
| `merchantReturnPolicy()` | `MerchantReturnPolicy` | Politique de retour |
| `monetaryAmount()` | `MonetaryAmount` | Montant monetaire |
| `review()` | `Review` | Avis |
| `rating()` | `Rating` | Note |
| `videoObject()` | `VideoObject` | Video |
| `clip()` | `Clip` | Extrait video (moment cle) |
| `faqPage()` | `FAQPage` | Page FAQ |
| `question()` | `Question` | Question (FAQ) |
| `answer()` | `Answer` | Reponse (FAQ) |
| `jobPosting()` | `JobPosting` | Offre d'emploi |
| `type(string $type)` | `SchemaType` | Type generique par nom |
| `ref(string $id)` | `SchemaRef` | Reference `@id` vers un autre noeud |

---

## SchemaType (classe de base)

`BackTo\Framework\Bundle\Seo\Schema\SchemaType`

Classe de base pour tous les types Schema.org. Implemente `JsonSerializable`.

| Methode | Signature | Description |
|---|---|---|
| `__construct` | `(string $type)` | Cree un schema avec le `@type` donne |
| `set` | `(string $property, mixed $value): static` | Definit une propriete (chainage fluide) |
| `id` | `(string $id): static` | Definit le `@id` du noeud |
| `getType` | `(): string` | Retourne le `@type` |
| `getProperties` | `(): array<string, mixed>` | Retourne toutes les proprietes |
| `toArray` | `(): array<string, mixed>` | Convertit en tableau JSON-LD |
| `validate` | `(): string[]` | Retourne les proprietes requises manquantes |
| `isValid` | `(): bool` | `true` si toutes les proprietes requises sont presentes |
| `jsonSerialize` | `(): mixed` | Serialisation JSON (appelle `toArray()`) |

**Methode protegee** : `getRequiredProperties(): string[]` -- a surcharger dans les classes concretes pour declarer les proprietes requises par Google.

---

## SchemaRef

`BackTo\Framework\Bundle\Seo\Schema\SchemaRef`

Reference vers un autre noeud du graphe JSON-LD par son `@id`. Implemente `JsonSerializable`.

| Methode | Signature | Description |
|---|---|---|
| `__construct` | `(string $id)` | Cree une reference |
| `getId` | `(): string` | Retourne l'identifiant |
| `toArray` | `(): array{@id: string}` | `['@id' => '...']` |

Usage : `Schema::ref('#organization')` produit `{"@id": "#organization"}`.

---

## SchemaManager

`BackTo\Framework\Bundle\Seo\Schema\SchemaManager`

Registre central des schemas. Collecte les `SchemaType` et les rend en JSON-LD.

| Methode | Signature | Description |
|---|---|---|
| `add` | `(SchemaType $schema): self` | Ajoute un schema au registre |
| `hasSchemas` | `(): bool` | `true` si au moins un schema est enregistre |
| `getSchemas` | `(): SchemaType[]` | Retourne tous les schemas |
| `validate` | `(): array<string, string[]>` | Valide tous les schemas, retourne les erreurs par type |
| `toArray` | `(): array<int, array>` | Exporte en tableau de JSON-LD |
| `render` | `(): string` | Genere la balise `<script type="application/ld+json">` |

Comportement de `render()` :
- 0 schemas : retourne une chaine vide
- 1 schema : objet JSON-LD simple avec `@context`
- 2+ schemas : objet avec `@context` et `@graph` contenant tous les schemas

---

## Les 31 types Schema.org

Chaque type herite de `SchemaType` et se trouve dans `BackTo\Framework\Bundle\Seo\Schema\Type\`.

### AggregateRating

| Methode | Signature |
|---|---|
| `ratingValue` | `(float\|string $value): static` |
| `bestRating` | `(float\|string $value): static` |
| `worstRating` | `(float\|string $value): static` |
| `ratingCount` | `(int $count): static` |
| `reviewCount` | `(int $count): static` |

### Answer

| Methode | Signature |
|---|---|
| `text` | `(string $text): static` |

### Article

Proprietes requises : `headline`, `author`, `datePublished`

| Methode | Signature |
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

### Brand

| Methode | Signature |
|---|---|
| `name` | `(string $name): static` |
| `url` | `(string $url): static` |
| `logo` | `(string\|SchemaType $logo): static` |

### BreadcrumbList

| Methode | Signature |
|---|---|
| `items` | `(SchemaType[] $items): static` |

Definit `itemListElement` a partir d'un tableau de `ListItem`.

### Clip

| Methode | Signature |
|---|---|
| `name` | `(string $name): static` |
| `startOffset` | `(int $seconds): static` |
| `endOffset` | `(int $seconds): static` |
| `url` | `(string $url): static` |

### Course

Proprietes requises : `name`, `description`, `provider`

| Methode | Signature |
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

### CourseInstance

| Methode | Signature |
|---|---|
| `courseMode` | `(string $mode): static` |
| `startDate` | `(string $date): static` |
| `endDate` | `(string $date): static` |
| `location` | `(SchemaType\|string $location): static` |
| `instructor` | `(SchemaType $instructor): static` |
| `inLanguage` | `(string $language): static` |
| `offers` | `(SchemaType\|array $offers): static` |
| `courseSchedule` | `(SchemaType $schedule): static` |

### Event

Proprietes requises : `name`, `startDate`, `location`

| Methode | Signature |
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

### FAQPage

Proprietes requises : `mainEntity`

| Methode | Signature |
|---|---|
| `mainEntity` | `(SchemaType[] $questions): static` |

### GeoCoordinates

| Methode | Signature |
|---|---|
| `latitude` | `(float $latitude): static` |
| `longitude` | `(float $longitude): static` |

### ImageObject

| Methode | Signature |
|---|---|
| `url` | `(string $url): static` |
| `width` | `(int $width): static` |
| `height` | `(int $height): static` |
| `caption` | `(string $caption): static` |

### JobPosting

Proprietes requises : `title`, `description`, `datePosted`, `hiringOrganization`

| Methode | Signature |
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

### ListItem

| Methode | Signature |
|---|---|
| `position` | `(int $position): static` |
| `name` | `(string $name): static` |
| `url` | `(string $url): static` |

Note : `url()` definit la propriete `item` dans le JSON-LD.

### LocalBusiness

Proprietes requises : `name`, `address`

| Methode | Signature |
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

### MerchantReturnPolicy

| Methode | Signature |
|---|---|
| `applicableCountry` | `(string $country): static` |
| `returnPolicyCategory` | `(string $category): static` |
| `merchantReturnDays` | `(int $days): static` |
| `returnMethod` | `(string $method): static` |
| `returnFees` | `(string $fees): static` |

### MonetaryAmount

| Methode | Signature |
|---|---|
| `currency` | `(string $currency): static` |
| `value` | `(float\|int\|SchemaType $value): static` |
| `minValue` | `(float\|int $value): static` |
| `maxValue` | `(float\|int $value): static` |

### Offer

Proprietes requises : `price`, `priceCurrency`

| Methode | Signature |
|---|---|
| `price` | `(string\|float\|int $price): static` |
| `priceCurrency` | `(string $currency): static` |
| `url` | `(string $url): static` |
| `availability` | `(string $availability): static` |
| `validFrom` | `(string $date): static` |
| `validThrough` | `(string $date): static` |
| `category` | `(string $category): static` |

### OfferShippingDetails

| Methode | Signature |
|---|---|
| `shippingRate` | `(SchemaType $rate): static` |
| `shippingDestination` | `(SchemaType $destination): static` |
| `deliveryTime` | `(SchemaType $time): static` |

### Organization

| Methode | Signature |
|---|---|
| `name` | `(string $name): static` |
| `url` | `(string $url): static` |
| `logo` | `(string\|SchemaType $logo): static` |
| `sameAs` | `(array $urls): static` |
| `description` | `(string $description): static` |
| `email` | `(string $email): static` |
| `telephone` | `(string $telephone): static` |
| `address` | `(SchemaType $address): static` |

### Person

| Methode | Signature |
|---|---|
| `name` | `(string $name): static` |
| `url` | `(string $url): static` |
| `email` | `(string $email): static` |
| `image` | `(string\|SchemaType $image): static` |
| `sameAs` | `(array $urls): static` |

### Place

| Methode | Signature |
|---|---|
| `name` | `(string $name): static` |
| `address` | `(SchemaType\|string $address): static` |
| `geo` | `(SchemaType $geo): static` |
| `url` | `(string $url): static` |

### PostalAddress

| Methode | Signature |
|---|---|
| `streetAddress` | `(string $street): static` |
| `addressLocality` | `(string $locality): static` |
| `addressRegion` | `(string $region): static` |
| `postalCode` | `(string $postalCode): static` |
| `addressCountry` | `(string $country): static` |

### Product

Proprietes requises : `name`, `image`, `offers`

| Methode | Signature |
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

### Question

| Methode | Signature |
|---|---|
| `name` | `(string $question): static` |
| `acceptedAnswer` | `(SchemaType $answer): static` |
| `suggestedAnswer` | `(array $answers): static` |

### Rating

| Methode | Signature |
|---|---|
| `ratingValue` | `(float\|string $value): static` |
| `bestRating` | `(float\|string $value): static` |
| `worstRating` | `(float\|string $value): static` |

### Review

Proprietes requises : `itemReviewed`, `reviewRating`, `author`

| Methode | Signature |
|---|---|
| `itemReviewed` | `(SchemaType $item): static` |
| `reviewRating` | `(SchemaType $rating): static` |
| `author` | `(SchemaType\|string $author): static` |
| `datePublished` | `(string $date): static` |
| `reviewBody` | `(string $body): static` |
| `name` | `(string $name): static` |
| `publisher` | `(SchemaType $publisher): static` |

### SearchAction

| Methode | Signature |
|---|---|
| `target` | `(string $urlTemplate): static` |
| `queryInput` | `(string $input): static` |

### VideoObject

Proprietes requises : `name`, `thumbnailUrl`, `uploadDate`

| Methode | Signature |
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

### VirtualLocation

| Methode | Signature |
|---|---|
| `url` | `(string $url): static` |

### WebSite

| Methode | Signature |
|---|---|
| `name` | `(string $name): static` |
| `url` | `(string $url): static` |
| `description` | `(string $description): static` |
| `publisher` | `(SchemaType $publisher): static` |
| `inLanguage` | `(string $language): static` |
| `potentialAction` | `(SchemaType $action): static` |

---

## Interfaces (Contracts)

### SeoProviderInterface

`BackTo\Framework\Bundle\Seo\Contracts\SeoProviderInterface`

Etend `SocialLinksProviderInterface` et `MetaProviderInterface`.

| Methode | Signature | Description |
|---|---|---|
| `getName` | `(): string` | Identifiant du plugin (`'yoast'`, `'seopress'`) |
| `isActive` | `(): bool` | Le plugin SEO est-il actif ? |

### MetaProviderInterface

`BackTo\Framework\Bundle\Seo\Contracts\MetaProviderInterface`

| Methode | Signature |
|---|---|
| `getTitle` | `(?int $postId = null): ?string` |
| `getDescription` | `(?int $postId = null): ?string` |
| `getCanonicalUrl` | `(?int $postId = null): ?string` |
| `getOgTitle` | `(?int $postId = null): ?string` |
| `getOgDescription` | `(?int $postId = null): ?string` |
| `getOgImageUrl` | `(?int $postId = null): ?string` |

### SocialLinksProviderInterface

`BackTo\Framework\Bundle\Seo\Contracts\SocialLinksProviderInterface`

| Methode | Signature |
|---|---|
| `getFacebookUrl` | `(): ?string` |
| `getTwitterUrl` | `(): ?string` |
| `getInstagramUrl` | `(): ?string` |
| `getLinkedInUrl` | `(): ?string` |
| `getPinterestUrl` | `(): ?string` |
| `getYouTubeUrl` | `(): ?string` |
| `getSocialLinks` | `(): array<string, string\|null>` |

### SchemaProviderInterface

`BackTo\Framework\Bundle\Seo\Contracts\SchemaProviderInterface`

| Methode | Signature |
|---|---|
| `registerSchemas` | `(SchemaManager $schemaManager): void` |

### BreadcrumbSchemaGeneratorInterface

`BackTo\Framework\Bundle\Seo\Contracts\BreadcrumbSchemaGeneratorInterface`

| Methode | Signature |
|---|---|
| `generate` | `(): ?SchemaType` |

---

## Providers

### YoastProvider

`BackTo\Framework\Bundle\Seo\Provider\YoastProvider`

Implemente `SeoProviderInterface`. Detecte `wordpress-seo/wp-seo.php`. Lit les options sociales depuis `wpseo_social` et les metadonnees depuis les post meta `_yoast_wpseo_*`.

### SeoPressProvider

`BackTo\Framework\Bundle\Seo\Provider\SeoPressProvider`

Implemente `SeoProviderInterface`. Detecte `wp-seopress/seopress.php`. Lit les options sociales depuis `seopress_social_option_name` et les metadonnees depuis les post meta `_seopress_*`.

---

## Generateurs

### WebSiteSchemaGenerator

`BackTo\Framework\Bundle\Seo\Schema\Generator\WebSiteSchemaGenerator`

Genere un `WebSite` avec `@id` `{siteUrl}/#website`, lie a `#organization` via `publisher`, avec un `SearchAction`.

### OrganizationSchemaGenerator

`BackTo\Framework\Bundle\Seo\Schema\Generator\OrganizationSchemaGenerator`

Genere un `Organization` avec `@id` `{siteUrl}/#organization`, le logo du theme (`custom_logo`) et les liens `sameAs` depuis le provider SEO.

### ArticleSchemaGenerator

`BackTo\Framework\Bundle\Seo\Schema\Generator\ArticleSchemaGenerator`

Genere un `Article` pour les posts de type `post`. Inclut headline, auteur, dates, image a la une, description. Lie a `#website` et `#organization`.

### PostTypeSchemaResolver

`BackTo\Framework\Bundle\Seo\Schema\Generator\PostTypeSchemaResolver`

Resout le generateur de schema pour un post type donne, selon le mapping `schema.post_type_map` du fichier `config/seo.php`.

| Methode | Signature | Description |
|---|---|---|
| `addGenerator` | `(string $postType, object $generator): self` | Enregistre un generateur |
| `supports` | `(string $postType): bool` | Un generateur existe-t-il ? |
| `resolve` | `(string $postType, ?int $postId = null): ?SchemaType` | Genere le schema |
| `getGenerators` | `(): array<string, object>` | Tous les generateurs |

### WordPressBreadcrumbSchemaGenerator

`BackTo\Framework\Bundle\Seo\Infrastructure\WordPressBreadcrumbSchemaGenerator`

Implemente `BreadcrumbSchemaGeneratorInterface`. Construit un `BreadcrumbList` a partir du contexte WordPress (page, article, archive, taxonomie, auteur, recherche).

---

## SeoManager

`BackTo\Framework\Bundle\Seo\SeoManager`

Resout le provider SEO actif parmi les providers enregistres.

| Methode | Signature | Description |
|---|---|---|
| `addProvider` | `(SeoProviderInterface $provider): self` | Ajoute un provider |
| `getProvider` | `(): ?SeoProviderInterface` | Retourne le premier provider actif |
| `hasProvider` | `(): bool` | Un plugin SEO est-il actif ? |
| `getSocialLinks` | `(): array<string, string\|null>` | Liens sociaux du provider actif |

---

## SeoConfig

`BackTo\Framework\Bundle\Seo\SeoConfig`

Charge la configuration depuis `config/seo.php` du theme.

| Methode | Signature | Description |
|---|---|---|
| `all` | `(): array` | Configuration complete |
| `get` | `(string $key, mixed $default = null): mixed` | Valeur par notation pointee |
| `getPostTypeMap` | `(): array<string, class-string>` | Mapping post type -> generateur |
| `shouldDisablePluginSchema` | `(): bool` | Desactiver le schema du plugin ? (defaut : `true`) |

---

## SeoConfigurator

`BackTo\Framework\Bundle\Seo\SeoConfigurator`

Configurateur fluide pour les parametres SEO du conteneur.

| Methode | Signature | Description |
|---|---|---|
| `titleSeparator` | `(string $separator): self` | Separateur de titre (defaut : `\|`) |
| `robotsDefault` | `(string $robots): self` | Directive robots par defaut (defaut : `index, follow`) |
| `toParameters` | `(): array<string, mixed>` | Exporte les parametres |

---

## SeoConfiguration

`BackTo\Framework\Bundle\Seo\SeoConfiguration`

Valeurs par defaut des parametres SEO.

| Parametre | Valeur par defaut |
|---|---|
| `seo.title_separator` | `\|` |
| `seo.robots_default` | `index, follow` |

---

## Hooks et Actions

### Hooks WordPress enregistres

| Classe | Hook | Priorite | Description |
|---|---|---|---|
| `RegisterDefaultSchemas` | `wp` | defaut | Enregistre WebSite, Organization, Article, BreadcrumbList |
| `InjectSchemaInHead` | `wp_head` | 1 | Injecte le JSON-LD dans le `<head>` |
| `AddSchemaToTimberContext` | `timber/context` | defaut | Ajoute `schema` (SchemaManager) au contexte Twig |
| `AddSocialLinksToTimberContext` | `timber/context` | defaut | Ajoute `facebook`, `twitter`, etc. au contexte Twig |
| `CleanYoastFootprint` | `wpseo_debug_markers`, `wpseo_hide_version` | defaut | Supprime les marqueurs Yoast |
| `DisablePluginSchema` | `wpseo_json_ld_output` ou `seopress_schemas_auto_enabled` | defaut | Desactive le schema du plugin |

### Action personnalisee

| Action | Parametres | Description |
|---|---|---|
| `framework/seo/schema` | `(SchemaManager $manager)` | Fired apres les schemas par defaut, permet d'ajouter des schemas personnalises |

---

## Arborescence des fichiers

```
src/Bundle/Seo/
├── Actions/
│   ├── CleanYoastFootprint.php
│   └── DisablePluginSchema.php
├── Contracts/
│   ├── BreadcrumbSchemaGeneratorInterface.php
│   ├── MetaProviderInterface.php
│   ├── SchemaProviderInterface.php
│   ├── SeoProviderInterface.php
│   └── SocialLinksProviderInterface.php
├── Hooks/
│   ├── AddSchemaToTimberContext.php
│   ├── AddSocialLinksToTimberContext.php
│   ├── InjectSchemaInHead.php
│   └── RegisterDefaultSchemas.php
├── Infrastructure/
│   └── WordPressBreadcrumbSchemaGenerator.php
├── Provider/
│   ├── SeoPressProvider.php
│   └── YoastProvider.php
├── Schema/
│   ├── Generator/
│   │   ├── ArticleSchemaGenerator.php
│   │   ├── BreadcrumbSchemaGenerator.php (deprecated)
│   │   ├── OrganizationSchemaGenerator.php
│   │   ├── PostTypeSchemaResolver.php
│   │   └── WebSiteSchemaGenerator.php
│   ├── Type/
│   │   ├── AggregateRating.php
│   │   ├── Answer.php
│   │   ├── Article.php
│   │   ├── Brand.php
│   │   ├── BreadcrumbList.php
│   │   ├── Clip.php
│   │   ├── Course.php
│   │   ├── CourseInstance.php
│   │   ├── Event.php
│   │   ├── FAQPage.php
│   │   ├── GeoCoordinates.php
│   │   ├── ImageObject.php
│   │   ├── JobPosting.php
│   │   ├── ListItem.php
│   │   ├── LocalBusiness.php
│   │   ├── MerchantReturnPolicy.php
│   │   ├── MonetaryAmount.php
│   │   ├── Offer.php
│   │   ├── OfferShippingDetails.php
│   │   ├── Organization.php
│   │   ├── Person.php
│   │   ├── Place.php
│   │   ├── PostalAddress.php
│   │   ├── Product.php
│   │   ├── Question.php
│   │   ├── Rating.php
│   │   ├── Review.php
│   │   ├── SearchAction.php
│   │   ├── VideoObject.php
│   │   ├── VirtualLocation.php
│   │   └── WebSite.php
│   ├── SchemaManager.php
│   ├── SchemaRef.php
│   └── SchemaType.php
├── Schema.php
├── SeoConfig.php
├── SeoConfiguration.php
├── SeoConfigurator.php
├── SeoExtension.php
└── SeoManager.php
```
