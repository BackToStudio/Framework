# Static vs dynamic blocks

A **static block** is defined entirely in the editor (JavaScript). Its HTML is saved to the database and served as-is.

A **dynamic block** implements the `DynamicBlock` interface and provides a `renderBlock()` method. WordPress calls this PHP method on every page load to generate the HTML server-side. This is useful for blocks that depend on fresh data (recent posts, user-specific content, etc.).

The bundle handles both cases transparently. The only difference for the developer is implementing the `DynamicBlock` interface and its `renderBlock()` method.
