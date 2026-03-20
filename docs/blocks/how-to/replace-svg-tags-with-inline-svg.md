# Replace SVG `<img>` tags with inline SVG

Enable `ReplaceImgBlockBySvgBlock` in your service configuration. It hooks into the `render_block_core/image` filter and replaces `<img>` tags pointing to SVG files with the actual SVG content inline, using `ReplaceImgTagBySvgTag`.

No configuration is needed — the action is self-contained.
