# Classes

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
