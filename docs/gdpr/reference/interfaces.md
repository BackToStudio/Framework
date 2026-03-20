# Interfaces

### `ConsentCategoryInterface`

**Namespace:** `BackTo\Framework\Bundle\Gdpr\Contracts`

Defines a consent category.

| Method | Return | Description |
|---|---|---|
| `getKey()` | `string` | Unique key (e.g. `analytics`, `marketing`) |
| `getLabel()` | `string` | Name displayed in the banner |
| `getDescription()` | `string` | Description displayed in the banner |
| `isRequired()` | `bool` | `true` if the category cannot be disabled |

---

### `TrackingScriptInterface`

**Namespace:** `BackTo\Framework\Bundle\Gdpr\Contracts`

Defines a tracking script.

| Method | Return | Description |
|---|---|---|
| `getHandle()` | `string` | Unique script identifier |
| `getCategoryKey()` | `string` | Associated consent category key |
| `getSource()` | `string` | Script URL or inline code |
| `isInline()` | `bool` | `true` = inline code, `false` = external `src` |
| `getLocation()` | `string` | `'head'` or `'footer'` |
| `getPriority()` | `int` | Load order (lower = earlier) |

---

### `ConsentStorageInterface`

**Namespace:** `BackTo\Framework\Bundle\Gdpr\Contracts`

Port for reading consent state.

| Method | Return | Description |
|---|---|---|
| `getConsent()` | `array<string, bool>` | Consent state per category |
| `hasConsent(string $categoryKey)` | `bool` | Check consent for a category |
| `isConsentGiven()` | `bool` | `true` if the user has made a choice |

---

### `ConsentCategoryRegistryInterface`

**Namespace:** `BackTo\Framework\Bundle\Gdpr\Contracts`

| Method | Return | Description |
|---|---|---|
| `add(ConsentCategoryInterface $category)` | `void` | Add a category |
| `getCategories()` | `array` | Return all categories |
| `get(string $key)` | `ConsentCategoryInterface` | Return a category by key |

---

### `TrackingScriptRegistryInterface`

**Namespace:** `BackTo\Framework\Bundle\Gdpr\Contracts`

| Method | Return | Description |
|---|---|---|
| `add(TrackingScriptInterface $script)` | `void` | Add a script |
| `getScripts()` | `array` | Return all scripts |
| `getScriptsByCategory(string $categoryKey)` | `array` | Filter scripts by category |
