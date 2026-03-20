# .htaccess directive strategy

### Marker-based injection

Directives are written to `.htaccess` using WordPress's `insert_with_markers()`, which manages a delimited block:

```
# BEGIN BackTo Performance
...directives...
# END BackTo Performance
```

This ensures the framework's directives do not interfere with WordPress core or other plugins. Updates overwrite only the BackTo Performance block. Deactivation removes the block cleanly.

### Apache modules

Each directive group is wrapped in `<IfModule>` for graceful degradation on servers where the module is unavailable:

- **`mod_deflate`** — Gzip compression for text-based resources. Already-compressed formats (images, video, woff2) are excluded via `SetEnvIfNoCase` to avoid double compression.
- **`mod_expires`** — Browser caching with differentiated TTLs. HTML gets TTL 0 (managed by the server-side page cache). Static assets get a configurable TTL (default 1 year). Dynamic data formats (JSON, XML) get TTL 0.
- **`mod_headers`** — `Cache-Control: public, max-age=..., immutable` for static assets (the `immutable` flag prevents conditional requests). ETag removal to avoid unnecessary revalidation. `Connection: keep-alive` for persistent connections.

### Write optimization

Writing to `.htaccess` is expensive (read, parse, write). To avoid redundant writes on every `admin_init`, an MD5 hash of the current directives is stored as a WordPress transient (key: `backto_htaccess_hash`, TTL: 24 hours). The file is only rewritten when the hash changes, meaning subsequent admin page loads trigger no file I/O as long as the configuration remains stable.
