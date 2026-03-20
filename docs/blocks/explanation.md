# Blocks Bundle — Architecture & Design

*Explanation — Understanding-oriented*

---

## Ports & adapters

The bundle applies the same hexagonal architecture as the rest of the framework. `BlockRegistrarInterface` and `BlockStyleRegistrarInterface` isolate business logic from WordPress functions. The adapters in `Infrastructure/` (`WordPressBlockRegistrar`, `WordPressBlockStyleRegistrar`) make the actual WordPress API calls.

---

## Block registration flow

1. Classes extending `CustomBlock` are auto-tagged `wordpress.block` by `BlocksExtension`.
2. `RegisterBlockPass` (compiler pass) collects them into `BlockRegistry`.
3. On the `init` hook, `RegisterBlock` iterates over the registry, checks whether each block already exists via `BlockRegistrarInterface::exists()`, and registers it. Blocks implementing `DynamicBlock` receive a `render_callback` pointing to their `renderBlock()` method.

---

## Block style registration flow

1. Classes extending `CustomBlockStyle` are auto-tagged `wordpress.block_style`.
2. `RegisterBlockStylePass` collects them into `BlockStyleRegistry`.
3. On the `after_setup_theme` hook, `RegisterBlockStyles` iterates over each style and calls `register_block_style()` for every block listed in the style's `$blocks` property.

---

## Static vs dynamic blocks

A **static block** is defined entirely in the editor (JavaScript). Its HTML is saved to the database and served as-is.

A **dynamic block** implements the `DynamicBlock` interface and provides a `renderBlock()` method. WordPress calls this PHP method on every page load to generate the HTML server-side. This is useful for blocks that depend on fresh data (recent posts, user-specific content, etc.).

The bundle handles both cases transparently. The only difference for the developer is implementing the `DynamicBlock` interface and its `renderBlock()` method.

---

## Why check for duplicates?

WordPress throws a notice if you register a block type that already exists. The bundle checks `WP_Block_Type_Registry` before every registration call, silently skipping duplicates. This avoids conflicts when multiple plugins or themes register the same block name.
