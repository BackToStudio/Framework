# Classes

### `ThemeKernel`

**Namespace:** `BackTo\Framework\Bundle\Theme`
**Extends:** `AbstractKernel`

Entry point for the theme.

#### Constructor

```php
new ThemeKernel(
    string $environment,
    bool $debug,
);
```

#### Inherited methods

| Method | Return | Description |
|---|---|---|
| `setProjectDir(string $dir)` | `void` | Set the theme root directory |
| `setTextDomain(string $domain)` | `void` | Set the text domain |
| `addExtension(ExtensionInterface $extension)` | `void` | Register a bundle extension |
| `boot()` | `void` | Compile the container and start hooks |

#### Protected methods

| Method | Return | Description |
|---|---|---|
| `getDirectoryParameterName()` | `string` | Returns `'themeDirectory'` |
| `getTextDomainParameterName()` | `string` | Returns `'themeTextDomain'` |
| `getKernelConfigDir()` | `string` | Path to `Resources/config` |

---

### `LoadThemeTextDomain`

**Namespace:** `BackTo\Framework\Bundle\Theme\I18n`
**Implements:** `Hooks`

Loads theme translations on the `after_setup_theme` hook via `load_theme_textdomain()`.

| Injected Parameter | Type | Description |
|---|---|---|
| `$themeDirectory` | `string` | Theme root directory |
| `$themeTextDomain` | `string` | Theme text domain |
