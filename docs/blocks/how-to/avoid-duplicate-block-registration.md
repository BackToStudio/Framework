# Avoid duplicate block registration

`RegisterBlock` automatically checks via `BlockRegistrarInterface::exists()` whether a block is already registered in `WP_Block_Type_Registry` before registering it. Duplicates are silently ignored. No action is needed on your part.
