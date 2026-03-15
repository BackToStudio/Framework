# How to Register Post Meta Fields

## Basic registration

Create a class implementing `PostMetaStructureInterface`:

```php
<?php

namespace MyTheme\PostMeta;

use BackTo\Framework\PostMeta\Entity\PostMetaStructure;

class EventDate extends PostMetaStructure
{
    // Configure via constructor or factory.
}
```

Or configure it via the `PostMetaStructureFactory`:

```php
<?php

namespace MyTheme\PostMeta;

use BackTo\Framework\PostMeta\Contracts\PostMetaStructureInterface;
use BackTo\Framework\PostMeta\Entity\PostMetaStructure;

class EventDate implements PostMetaStructureInterface
{
    // Implement all interface methods...
}
```

## Using the entity directly

The `PostMetaStructure` entity provides a fluent API:

```php
$meta = new PostMetaStructure();
$meta->setObjectType('post')
     ->setMetaKey('event_date')
     ->setType('string')
     ->setLabel('Event Date')
     ->setDescription('The date of the event')
     ->setSingle(true)
     ->showInRest()
     ->setSanitizeCallback('sanitize_text_field');
```

## Available options

| Method | Type | Default | Description |
|--------|------|---------|-------------|
| `setObjectType()` | `string` | `'post'` | The post type to attach meta to |
| `setMetaKey()` | `string` | `''` | The meta key name |
| `setType()` | `string` | `''` | Data type (`string`, `integer`, `boolean`, etc.) |
| `setLabel()` | `string` | `''` | Human-readable label |
| `setDescription()` | `string` | `''` | Description |
| `setSingle()` | `bool` | `true` | Whether to return a single value |
| `showInRest()` / `dontShowInRest()` | `bool` | `false` | REST API visibility |
| `setRevisionsEnabled()` | `bool` | `false` | Track revisions |
| `setDefault()` | `mixed` | `null` | Default value |
| `setSanitizeCallback()` | `callable` | `null` | Sanitization function |
| `setAuthCallback()` | `callable` | `null` | Authorization function |

## Reading post meta

Use the `PostMetaRepository` to query meta values:

```php
use BackTo\Framework\PostMeta\Repository\PostMetaRepository;

$repo = new PostMetaRepository();
$date = $repo->get($postId, 'event_date');
```
