# How to Register Custom Taxonomies

## Basic registration

Create a class that implements `TaxonomyInterface`:

```php
<?php

namespace MyTheme\Taxonomy;

use BackTo\Framework\Taxonomy\Contracts\TaxonomyInterface;

class Genre implements TaxonomyInterface
{
    public function getKey(): ?string
    {
        return 'genre';
    }

    public function getArgs(): array
    {
        return [
            'label' => 'Genres',
            'hierarchical' => true,
            'show_in_rest' => true,
        ];
    }

    public function getPostTypes(): array
    {
        return ['post', 'portfolio'];
    }
}
```

The framework auto-discovers it, tags it with `wordpress.taxonomy`, and registers it at the `init` hook.

## Flat taxonomy (like tags)

```php
public function getArgs(): array
{
    return [
        'label' => 'Skills',
        'hierarchical' => false,
        'show_in_rest' => true,
    ];
}
```

## Multiple post type association

Attach a taxonomy to several post types by returning them in `getPostTypes()`:

```php
public function getPostTypes(): array
{
    return ['post', 'page', 'portfolio'];
}
```

## Register on-the-fly

```php
$registerTaxonomy = $container->get(RegisterTaxonomy::class);
$registerTaxonomy->add('skill', ['portfolio'], ['label' => 'Skills', 'hierarchical' => false]);
```

## Default arguments

The `TaxonomyFactory` applies these defaults when not specified:

| Argument | Default |
|----------|---------|
| `show_ui` | `true` |
| `show_in_rest` | `true` |
| `publicly_queryable` | `true` |
