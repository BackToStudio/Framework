# Why generate structured data in PHP?

WordPress SEO plugins (Yoast, SEOPress) generate their own JSON-LD, but their output is limited to generic types (WebSite, Organization, Article). When a theme needs Product, Event, Course, or FAQ schemas tied to custom post types, plugin-generated markup is insufficient.

The SEO bundle places structured data generation in the theme's PHP layer, where it has full access to post data, custom fields, and business logic. The result is a single `<script type="application/ld+json">` block in the `<head>` with a coherent `@graph` linking all entities.
