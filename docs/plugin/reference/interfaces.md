# Interfaces

### `TextDomainLoaderInterface`

**Namespace:** `BackTo\Framework\Bundle\Plugin\Contracts`

Port for loading text domains.

| Method | Return | Description |
|---|---|---|
| `loadPluginTextDomain(string $domain, string $pluginRelPath)` | `void` | Load a plugin text domain |
| `loadMuPluginTextDomain(string $domain, string $muPluginRelPath)` | `void` | Load a mu-plugin text domain |
| `loadThemeTextDomain(string $domain, string $path)` | `void` | Load a theme text domain |
