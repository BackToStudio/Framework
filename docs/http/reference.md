# HTTP Client — API Reference

*Reference — Information-oriented*

---

## Interfaces

### `HttpClientInterface`

**Namespace:** `BackTo\Framework\Http\Contracts`
**Extends:** `Psr\Http\Client\ClientInterface` (PSR-18)

```php
interface HttpClientInterface extends ClientInterface
{
    // PSR-18 (inherited)
    public function sendRequest(RequestInterface $request): ResponseInterface;

    // Convenience methods
    public function get(string $url, array $options = []): ResponseInterface;
    public function post(string $url, array $options = []): ResponseInterface;
    public function request(string $method, string $url, array $options = []): ResponseInterface;
}
```

#### Options

| Key | Type | Default | Description |
|---|---|---|---|
| `timeout` | `int` | `5` | Request timeout in seconds |
| `headers` | `array<string, string>` | `[]` | HTTP request headers |
| `body` | `string\|array` | `''` | Request body. Arrays are form-encoded |
| `blocking` | `bool` | `true` | `false` = fire-and-forget (no response waiting) |
| `sslverify` | `bool` | `true` | Verify SSL certificates |
| `cookies` | `array` | `[]` | Cookies to send with the request |

---

## Classes

### `Response`

**Namespace:** `BackTo\Framework\Http`
**Implements:** `Psr\Http\Message\ResponseInterface` (PSR-7)

Immutable HTTP response. All `with*()` methods return a new instance.

```php
new Response(
    int $statusCode = 200,
    array $headers = [],
    string|StreamInterface $body = '',
    string $protocolVersion = '1.1',
    string $reasonPhrase = '',
);
```

#### Methods

| Method | Return | Description |
|---|---|---|
| `getStatusCode()` | `int` | HTTP status code |
| `getReasonPhrase()` | `string` | Reason phrase (auto-resolved from status code if empty) |
| `getHeaders()` | `array<string, string[]>` | All headers |
| `hasHeader(string $name)` | `bool` | Case-insensitive header check |
| `getHeader(string $name)` | `string[]` | Header values as array |
| `getHeaderLine(string $name)` | `string` | Header values joined by `, ` |
| `getBody()` | `StreamInterface` | Response body as PSR-7 stream |
| `getProtocolVersion()` | `string` | HTTP protocol version (`1.1`) |
| `withStatus(int $code, string $reasonPhrase = '')` | `static` | New instance with different status |
| `withHeader(string $name, $value)` | `static` | New instance with replaced header |
| `withAddedHeader(string $name, $value)` | `static` | New instance with appended header |
| `withoutHeader(string $name)` | `static` | New instance without the header |
| `withBody(StreamInterface $body)` | `static` | New instance with different body |
| `withProtocolVersion(string $version)` | `static` | New instance with different protocol |

#### Auto-resolved reason phrases

`200 OK`, `201 Created`, `204 No Content`, `301 Moved Permanently`, `302 Found`, `304 Not Modified`, `400 Bad Request`, `401 Unauthorized`, `403 Forbidden`, `404 Not Found`, `405 Method Not Allowed`, `409 Conflict`, `422 Unprocessable Entity`, `429 Too Many Requests`, `500 Internal Server Error`, `502 Bad Gateway`, `503 Service Unavailable`, `504 Gateway Timeout`

---

### `StringStream`

**Namespace:** `BackTo\Framework\Http`
**Implements:** `Psr\Http\Message\StreamInterface` (PSR-7)

In-memory stream backed by a PHP string.

```php
new StringStream(string $content = '');
```

#### Methods

| Method | Return | Description |
|---|---|---|
| `__toString()` | `string` | Full stream content |
| `getSize()` | `?int` | Content length in bytes |
| `tell()` | `int` | Current read/write position |
| `eof()` | `bool` | Whether position is at end |
| `isSeekable()` | `bool` | Always `true` (until detached) |
| `seek(int $offset, int $whence = SEEK_SET)` | `void` | Move position |
| `rewind()` | `void` | Reset position to 0 |
| `isWritable()` | `bool` | Always `true` (until detached) |
| `write(string $string)` | `int` | Write at current position, return bytes written |
| `isReadable()` | `bool` | Always `true` (until detached) |
| `read(int $length)` | `string` | Read bytes from current position |
| `getContents()` | `string` | Remaining content from position to end |
| `close()` | `void` | Release the stream |
| `detach()` | `null` | Detach and invalidate the stream |
| `getMetadata(?string $key = null)` | `mixed` | Stream metadata |

---

### `WordPressHttpClient`

**Namespace:** `BackTo\Framework\Http\Infrastructure`
**Implements:** `HttpClientInterface`

WordPress adapter wrapping `wp_remote_request()`. Registered automatically in the DI container.

- `sendRequest()` extracts method, URI, headers, and body from the PSR-7 `RequestInterface` and passes them to `wp_remote_request()`
- Convenience methods (`get`, `post`, `request`) map the `$options` array to WordPress's `$args` format
- `WP_Error` responses are converted to a `Response` with status code `0` and the error message as body
- WordPress response headers (including `CaseInsensitiveDictionary`) are normalized to `array<string, string[]>`

---

## DI Registration

The `HttpClientInterface` binding is registered in `HooksExtension`:

```php
$container->register(HttpClientInterface::class, WordPressHttpClient::class);
```

Autowiring resolves both `HttpClientInterface` and `Psr\Http\Client\ClientInterface` to the same instance.
