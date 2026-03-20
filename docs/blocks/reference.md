# Blocks Bundle — API Reference

*Reference — Information-oriented*

---

## Abstract Classes

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

---

## Interfaces

| Interface | Namespace | Methods |
|---|---|---|
| `BlockRegistrarInterface` | `Contracts` | `register(string $blockName, array $args)`, `exists(string $blockName)` |
| `BlockStyleRegistrarInterface` | `Contracts` | `register(string $blockName, array $styleProperties)` |

---

## Registries

| Class | Description |
|---|---|
| `BlockRegistry` | Collects `BlockInterface` instances |
| `BlockStyleRegistry` | Collects `BlockStyleInterface` instances |

**Namespace:** `BackTo\Framework\Bundle\Blocks`

---

## Actions

| Class | Hook | Description |
|---|---|---|
| `RegisterBlock` | `init` | Registers blocks from the registry via `register_block_type()` |
| `RegisterBlockStyles` | `after_setup_theme` | Registers styles via `register_block_style()` |
| `ReplaceImgBlockBySvgBlock` | `render_block_core/image` | Replaces SVG `<img>` tags with inline SVG |

**Namespace:** `BackTo\Framework\Bundle\Blocks` (actions in `Actions/`)

---

## Infrastructure

| Class | Description |
|---|---|
| `WordPressBlockRegistrar` | Adapter calling `register_block_type()` and `WP_Block_Type_Registry` |
| `WordPressBlockStyleRegistrar` | Adapter calling `register_block_style()` |

**Namespace:** `BackTo\Framework\Bundle\Blocks\Infrastructure`

---

## Compiler Passes

| Class | Tag | Target Registry |
|---|---|---|
| `RegisterBlockPass` | `wordpress.block` | `BlockRegistry` |
| `RegisterBlockStylePass` | `wordpress.block_style` | `BlockStyleRegistry` |

**Namespace:** `BackTo\Framework\Bundle\Blocks\DependencyInjection\Compiler`

---

## DI Extension

### `BlocksExtension`

**Namespace:** `BackTo\Framework\Bundle\Blocks`

Auto-tags classes implementing `BlockInterface` with `wordpress.block` and `BlockStyleInterface` with `wordpress.block_style`.
