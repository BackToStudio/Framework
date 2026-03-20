# Plugin Bundle — API Reference

*Reference — Information-oriented*

---

## Classes

### `PluginKernel`

**Namespace:** `BackTo\Framework\Bundle\Plugin`
**Extends:** `AbstractKernel`

Entry point for the plugin. Uses traits `TextDomain` and `WordPressContainer`.

#### Constructor

```php
new PluginKernel(
    string $environment,
    bool $debug,
);
```

#### Inherited methods

| Method | Return | Description |
|---|---|---|
| `setProjectDir(string $dir)` | `void` | Set the plugin root directory |
| `setTextDomain(string $domain)` | `void` | Set the text domain |
| `addExtension(ExtensionInterface $extension)` | `void` | Register a bundle extension |
| `boot()` | `void` | Compile the container and start hooks |

#### Protected methods

| Method | Return | Description |
|---|---|---|
| `getDirectoryParameterName()` | `string` | Returns `'pluginDirectory'` |
| `getTextDomainParameterName()` | `string` | Returns `'pluginTextDomain'` |
| `getKernelConfigDir()` | `string` | Path to `Resources/config` |

---

### `LoadPluginTextDomain`

**Namespace:** `BackTo\Framework\Bundle\Plugin\I18n`
**Implements:** `Hooks`

Loads plugin translations on the `init` hook via `load_plugin_textdomain()`.

| Injected Parameter | Type | Description |
|---|---|---|
| `$pluginDirectory` | `string` | Plugin root directory |
| `$pluginTextDomain` | `string` | Plugin text domain |

---

### `LoadMuPluginTextDomain`

**Namespace:** `BackTo\Framework\Bundle\Plugin\I18n`
**Implements:** `Hooks`

Loads mu-plugin translations on the `init` hook via `load_muplugin_textdomain()`.

| Injected Parameter | Type | Description |
|---|---|---|
| `$pluginDirectory` | `string` | Plugin root directory |
| `$pluginTextDomain` | `string` | Plugin text domain |

---

## Interfaces

### `TextDomainLoaderInterface`

**Namespace:** `BackTo\Framework\Bundle\Plugin\Contracts`

Port for loading text domains.

| Method | Return | Description |
|---|---|---|
| `loadPluginTextDomain(string $domain, string $pluginRelPath)` | `void` | Load a plugin text domain |
| `loadMuPluginTextDomain(string $domain, string $muPluginRelPath)` | `void` | Load a mu-plugin text domain |
| `loadThemeTextDomain(string $domain, string $path)` | `void` | Load a theme text domain |

---

## Infrastructure

### `WordPressTextDomainLoader`

**Namespace:** `BackTo\Framework\Bundle\Plugin\Infrastructure`

Adapter calling the native WordPress functions (`load_plugin_textdomain()`, `load_muplugin_textdomain()`, `load_theme_textdomain()`).

---

## Container Parameters

| Parameter | Description |
|---|---|
| `%pluginDirectory%` | Absolute path to the plugin root |
| `%pluginTextDomain%` | Plugin text domain string |
