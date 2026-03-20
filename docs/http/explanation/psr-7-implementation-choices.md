# PSR-7 implementation choices

The framework implements two PSR-7 interfaces internally:

### `Response` (PSR-7 `ResponseInterface`)

Needed because the WordPress adapter must return a standardized response object. The implementation is **immutable** (all `with*()` methods return new instances) as required by PSR-7.

### `StringStream` (PSR-7 `StreamInterface`)

The simplest possible stream: an in-memory string. HTTP response bodies in WordPress are always strings (loaded entirely into memory by `wp_remote_request()`), so a string-backed stream is the natural fit. No file handles, no resource management complexity.

### What about `Request`, `Uri`, `ServerRequest`?

These are **not** implemented. The framework only *creates* responses (converting WordPress responses to PSR-7). It *consumes* requests — and only through the `sendRequest()` method, where the caller provides their own PSR-7 request object.

If a developer needs PSR-7 request objects, they bring their own lightweight library (nyholm/psr7, guzzlehttp/psr7, etc.). This keeps the framework's dependency footprint minimal.
