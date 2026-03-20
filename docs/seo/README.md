# SEO

Donnees structurees Schema.org et integration des plugins SEO pour WordPress.

## Presentation

Le bundle SEO genere automatiquement des donnees structurees JSON-LD (Schema.org) et fournit une couche d'abstraction unifiee pour les plugins SEO (Yoast SEO, SEOPress). Il offre une API fluide pour construire 31 types Schema.org, les organise dans un graphe `@graph` avec des references `@id` croisees, et les injecte dans le `<head>` de chaque page.

Les schemas par defaut (WebSite, Organization, Article, BreadcrumbList) sont generes sans configuration. Les themes peuvent ajouter des schemas personnalises via le hook `framework/seo/schema` ou l'API fluide `Schema::product()`, `Schema::event()`, etc.

Les liens sociaux et les metadonnees SEO (titre, description, Open Graph) sont exposes dans le contexte Timber/Twig, quel que soit le plugin SEO installe.

## Documentation

| Document | Type | Description |
|---|---|---|
| [Premiers pas](tutorial.md) | Tutoriel | Decouvrir les schemas auto-generes, creer des schemas personnalises, integrer Yoast/SEOPress |
| [Recettes](how-to.md) | Guide pratique | Ajouter un schema pour un CPT, FAQ, Product, breadcrumbs, liens sociaux dans Twig |
| [Reference API](reference.md) | Reference | Factory `Schema`, `SchemaType`, `SchemaManager`, les 31 types, interfaces, hooks |
| [Architecture](explanation.md) | Explication | Pattern `@graph`, pipeline de generation, abstraction des providers SEO |
