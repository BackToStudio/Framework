# How to Register Custom Post Types

## Basic registration

Create a class that implements `PostTypeInterface`:

```php
<?php

namespace MyTheme\PostType;

use BackTo\Framework\PostType\Contracts\PostTypeInterface;

class Portfolio implements PostTypeInterface
{
    public function getKey(): string
    {
        return 'portfolio';
    }

    public function getArgs(): array
    {
        return [
            'label' => 'Portfolio',
            'public' => true,
            'has_archive' => true,
        ];
    }
}
```

The framework auto-discovers any class implementing `PostTypeInterface`, tags it with `wordpress.post_type`, and registers it through the `RegisterPostType` orchestrator at the `init` hook.

## Custom labels

```php
public function getArgs(): array
{
    return [
        'labels' => [
            'name' => 'Projects',
            'singular_name' => 'Project',
            'add_new_item' => 'Add New Project',
            'edit_item' => 'Edit Project',
        ],
        'public' => true,
        'has_archive' => true,
        'menu_icon' => 'dashicons-portfolio',
    ];
}
```

If you omit `labels`, the framework automatically generates singular and plural names from the key.

## Hierarchical post type

When `hierarchical` is `true`, the factory automatically adds `page-attributes`, `editor`, and `title` to the `supports` array:

```php
public function getArgs(): array
{
    return [
        'label' => 'Documentation',
        'hierarchical' => true,
        'public' => true,
    ];
}
```

## Editor support

When `editor` is present in `supports`, the factory automatically adds `custom-fields`, `revisions`, and `title`:

```php
public function getArgs(): array
{
    return [
        'label' => 'Articles',
        'public' => true,
        'supports' => ['editor', 'thumbnail'],
        // 'custom-fields', 'revisions', 'title' added automatically
    ];
}
```

## Register on-the-fly

If you need to register a post type programmatically (outside of auto-discovery):

```php
$registerPostType = $container->get(RegisterPostType::class);
$registerPostType->add('dynamic_type', ['label' => 'Dynamic', 'public' => true]);
```

## Default arguments

The `PostTypeFactory` applies these defaults when not specified:

| Argument | Default |
|----------|---------|
| `show_ui` | `true` |
| `show_in_rest` | `true` |
| `publicly_queryable` | `true` |
