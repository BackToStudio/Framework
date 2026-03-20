# Interfaces

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
