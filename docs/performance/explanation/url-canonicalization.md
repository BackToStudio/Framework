# URL canonicalization

The `RequestUrlResolver` builds the cache key by normalizing the current request URL. The host is validated against the configured `site_url` to prevent cache poisoning via a spoofed `Host` header. Query strings are stripped (requests with query parameters are never cached). This ensures that the same page always produces the same cache key regardless of HTTP header variations.
