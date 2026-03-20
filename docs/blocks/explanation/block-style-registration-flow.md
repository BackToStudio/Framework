# Block style registration flow

1. Classes extending `CustomBlockStyle` are auto-tagged `wordpress.block_style`.
2. `RegisterBlockStylePass` collects them into `BlockStyleRegistry`.
3. On the `after_setup_theme` hook, `RegisterBlockStyles` iterates over each style and calls `register_block_style()` for every block listed in the style's `$blocks` property.
