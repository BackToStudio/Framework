# Entities

### `ConsentCategory`

**Namespace:** `BackTo\Framework\Bundle\Gdpr\Entity`
**Implements:** `ConsentCategoryInterface`

```php
new ConsentCategory(
    key: 'analytics',
    label: 'Analytics',
    description: 'Audience measurement cookies.',
    required: false, // default
);
```

---

### `TrackingScript`

**Namespace:** `BackTo\Framework\Bundle\Gdpr\Entity`
**Implements:** `TrackingScriptInterface`

Validates parameters at construction: `$handle` and `$source` must not be empty, `$location` must be `'head'` or `'footer'`.

```php
new TrackingScript(
    handle: 'facebook-pixel',
    categoryKey: 'marketing',
    source: '!function(f,b,e,...)',
    inline: true,
    location: 'head',
    priority: 5,
);
```
