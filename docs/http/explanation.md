# HTTP Client — Architecture & Design

*Explanation — Understanding-oriented*

---

## Why a PSR-18 HTTP client in a WordPress framework?

WordPress provides `wp_remote_get()` and `wp_remote_post()`, but calling them directly in domain code creates tight coupling. Business logic becomes untestable without a running WordPress instance, and swapping the HTTP transport (for testing, queuing, or non-WordPress contexts) is impossible.

The HTTP module solves this by placing a **port interface** between domain code and WordPress's HTTP API, following the Hexagonal Architecture pattern used throughout the framework.

---

## Why PSR-18 specifically?

PSR-18 (`Psr\Http\Client\ClientInterface`) is the PHP industry standard for HTTP clients. By extending it rather than creating a proprietary interface, the framework gains:

1. **Interoperability** — Any library that accepts a PSR-18 client works out of the box (SDKs, API wrappers, monitoring tools)
2. **Familiarity** — Developers who know Guzzle or Symfony HttpClient recognize `sendRequest(RequestInterface): ResponseInterface` instantly
3. **Swappability** — The WordPress adapter can be replaced by Guzzle, Symfony HttpClient, or a test double without changing consumer code

---

## Interface design: PSR-18 + convenience methods

PSR-18 defines a single method:

```php
public function sendRequest(RequestInterface $request): ResponseInterface;
```

This is powerful but verbose for simple cases — you need to create a PSR-7 `Request` object with a `Uri`, headers, and body before sending it. For a WordPress framework where most HTTP calls are simple GET/POST requests, this creates unnecessary friction.

The framework's `HttpClientInterface` extends PSR-18 and adds three convenience methods:

```php
interface HttpClientInterface extends ClientInterface
{
    public function get(string $url, array $options = []): ResponseInterface;
    public function post(string $url, array $options = []): ResponseInterface;
    public function request(string $method, string $url, array $options = []): ResponseInterface;
}
```

This layered approach means:
- **Simple use cases** use `get()` / `post()` — no PSR-7 request creation needed
- **Advanced use cases** use `sendRequest()` — full PSR-18 with any PSR-7 library
- **Third-party libraries** type-hint against `ClientInterface` — satisfied by our interface

---

## PSR-7 implementation choices

The framework implements two PSR-7 interfaces internally:

### `Response` (PSR-7 `ResponseInterface`)

Needed because the WordPress adapter must return a standardized response object. The implementation is **immutable** (all `with*()` methods return new instances) as required by PSR-7.

### `StringStream` (PSR-7 `StreamInterface`)

The simplest possible stream: an in-memory string. HTTP response bodies in WordPress are always strings (loaded entirely into memory by `wp_remote_request()`), so a string-backed stream is the natural fit. No file handles, no resource management complexity.

### What about `Request`, `Uri`, `ServerRequest`?

These are **not** implemented. The framework only *creates* responses (converting WordPress responses to PSR-7). It *consumes* requests — and only through the `sendRequest()` method, where the caller provides their own PSR-7 request object.

If a developer needs PSR-7 request objects, they bring their own lightweight library (nyholm/psr7, guzzlehttp/psr7, etc.). This keeps the framework's dependency footprint minimal.

---

## WordPress adapter: how the mapping works

```
HttpClientInterface::get($url, $options)
        │
        ▼
WordPressHttpClient::request('GET', $url, $options)
        │
        ├── buildArgs(): maps $options to WordPress $args format
        │   ├── timeout → timeout
        │   ├── headers → headers
        │   ├── body → body
        │   ├── blocking → blocking
        │   ├── sslverify → sslverify
        │   └── cookies → cookies
        │
        ├── wp_remote_request($url, $args)
        │
        └── toResponse(): converts WP response to PSR-7
            ├── WP_Error → Response(0, [], $errorMessage)
            └── array → Response($statusCode, $headers, $body)
```

The `sendRequest(RequestInterface)` path works differently: it extracts method, URI, headers, and body from the PSR-7 request object and passes them to `wp_remote_request()`.

---

## Non-blocking requests

WordPress supports non-blocking HTTP via `['blocking' => false]`. The request is dispatched and execution continues immediately. The returned response has a `0` status code and empty body.

This is used internally by `PreloadExecutor` for cache warming: it fires GET requests to the site's own pages without waiting for responses, letting WordPress's page cache populate in the background.

---

## Error handling strategy

Network errors (DNS failure, timeout, SSL issues) in WordPress return a `WP_Error` object. The adapter converts these to a `Response` with:
- Status code: `0` (distinguishes network errors from HTTP errors like 404/500)
- Body: the error message from `WP_Error`

This avoids throwing exceptions for network issues, keeping the API consistent: every call returns a `ResponseInterface`. Consumers check `$response->getStatusCode() === 0` for network failures.

---

## Dependencies

| Package | Type | Purpose |
|---|---|---|
| `psr/http-client` | Interface only | PSR-18 `ClientInterface` |
| `psr/http-message` | Interface only | PSR-7 `ResponseInterface`, `StreamInterface` |

Both are **interface-only packages** (no implementation code). They are not vendor-scoped because PSR interfaces are industry standards designed to be shared across packages.
