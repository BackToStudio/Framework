# Abstract Classes

### `CustomBlock`

**Namespace:** `BackTo\Framework\Bundle\Blocks`
**Implements:** `BlockInterface`

Base class for defining a custom block.

| Property | Type | Description |
|---|---|---|
| `$name` | `string` | Block name (e.g. `my-theme/hero`) |

| Method | Return | Description |
|---|---|---|
| `getName()` | `string` | Returns the block name |

---

### `CustomBlockStyle`

**Namespace:** `BackTo\Framework\Bundle\Blocks`
**Implements:** `BlockStyleInterface`

Base class for defining a block style.

| Property | Type | Description |
|---|---|---|
| `$blocks` | `string[]` | Block names to apply the style to |
| `$styleName` | `string` | Style identifier |
| `$label` | `string` | Label displayed in the editor |

| Method | Return | Description |
|---|---|---|
| `getStyleName()` | `string` | Returns the style identifier |
| `getLabel()` | `string` | Returns the label |
| `getBlocks()` | `string[]` | Returns the target block names |
| `getProperties()` | `array{name, label}` | Returns the properties array for `register_block_style()` |
