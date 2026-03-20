# Block registration flow

1. Classes extending `CustomBlock` are auto-tagged `wordpress.block` by `BlocksExtension`.
2. `RegisterBlockPass` (compiler pass) collects them into `BlockRegistry`.
3. On the `init` hook, `RegisterBlock` iterates over the registry, checks whether each block already exists via `BlockRegistrarInterface::exists()`, and registers it. Blocks implementing `DynamicBlock` receive a `render_callback` pointing to their `renderBlock()` method.
