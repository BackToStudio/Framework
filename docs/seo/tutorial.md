# Getting started with the SEO Bundle

*Tutorial -- Learning-oriented*

This tutorial walks you through adding structured data to a blog post and validating it. By the end, you will have an Article schema rendered as JSON-LD in your page's `<head>` and confirmed valid with Google's Rich Results Test.

## Prerequisites

- A WordPress theme using the BackTo Framework
- The framework's DI container configured (the SEO bundle is auto-wired)
- A published blog post to work with

## Step 1: See what the bundle generates by default

Without any configuration, the SEO bundle automatically generates four schemas on every page: WebSite, Organization, BreadcrumbList, and Article (on `post` pages).

Open your blog post in a browser, view the page source, and search for `application/ld+json`. You will find a `<script>` block containing a JSON-LD `@graph`:

```json
{
  "@context": "https://schema.org",
  "@graph": [
    {
      "@type": "WebSite",
      "@id": "https://example.com/#website",
      "name": "My Site",
      "url": "https://example.com/"
    },
    {
      "@type": "Organization",
      "@id": "https://example.com/#organization",
      "name": "My Site",
      "url": "https://example.com/"
    },
    {
      "@type": "Article",
      "@id": "https://example.com/my-post/#article",
      "headline": "My Post Title",
      "datePublished": "2025-01-15T10:00:00+00:00",
      "author": { "@type": "Person", "name": "Jane Doe" }
    },
    {
      "@type": "BreadcrumbList",
      "itemListElement": [
        { "@type": "ListItem", "position": 1, "name": "Home", "item": "https://example.com/" },
        { "@type": "ListItem", "position": 2, "name": "My Post Title" }
      ]
    }
  ]
}
```

The nodes reference each other via `@id` -- the Article points to the WebSite and Organization without duplicating their data.

## Step 2: Add a custom schema via the hook

The bundle fires a `framework/seo/schema` action after registering its default schemas. Hook into it to add your own.

Create a file in your theme's `src/` directory:

```php
<?php

namespace MyTheme\Seo;

use BackTo\Framework\Bundle\Seo\Schema;
use BackTo\Framework\Bundle\Seo\Schema\SchemaManager;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

final class AddFaqSchema implements Hooks
{
    public function __construct(
        private readonly HookDispatcherInterface $hookDispatcher,
    ) {}

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('framework/seo/schema', [$this, 'register']);
    }

    public function register(SchemaManager $manager): void
    {
        $faq = Schema::faqPage()->mainEntity([
            Schema::question()
                ->name('What is the return policy?')
                ->acceptedAnswer(
                    Schema::answer()->text('You have 30 days to return any item in its original packaging.')
                ),
            Schema::question()
                ->name('Do you offer free shipping?')
                ->acceptedAnswer(
                    Schema::answer()->text('Yes, free shipping on all orders over $50.')
                ),
        ]);

        $manager->add($faq);
    }
}
```

Because `AddFaqSchema` implements `Hooks`, the framework auto-discovers it. Reload the page and check the source -- a `FAQPage` node now appears in the `@graph`.

## Step 3: Validate the output

The bundle includes built-in validation against Google's required properties. Try it in your schema hook:

```php
public function register(SchemaManager $manager): void
{
    $faq = Schema::faqPage()->mainEntity([
        Schema::question()
            ->name('What is the return policy?')
            ->acceptedAnswer(
                Schema::answer()->text('30-day returns.')
            ),
    ]);

    $missing = $faq->validate(); // [] -- all required properties present

    $manager->add($faq);
}
```

For external validation, copy the full JSON-LD output from your page source and paste it into [Google's Rich Results Test](https://search.google.com/test/rich-results). The FAQPage should appear as eligible for rich results.

## Step 4: Validate all schemas at once

The `SchemaManager` can validate every registered schema in a single call:

```php
public function register(SchemaManager $manager): void
{
    $manager->add(Schema::faqPage()->mainEntity([
        Schema::question()
            ->name('How does it work?')
            ->acceptedAnswer(Schema::answer()->text('Like this.')),
    ]));

    $errors = $manager->validate();
    // [] -- empty array means all schemas are valid
}
```

If a schema is missing required properties, `validate()` returns them keyed by type: `['Product' => ['image', 'offers']]`.

## Next steps

- See [Common tasks](how-to.md) for practical recipes (Product schema, custom breadcrumbs, social links in Twig)
- See [API reference](reference.md) for the complete list of schema types and their methods
- See [Architecture](explanation.md) to understand the `@graph` design and generation pipeline
