# Why check for duplicates?


WordPress throws a notice if you register a block type that already exists. The bundle checks `WP_Block_Type_Registry` before every registration call, silently skipping duplicates. This avoids conflicts when multiple plugins or themes register the same block name.
