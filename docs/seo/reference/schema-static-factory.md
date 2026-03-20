# `Schema` (static factory)

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
