# Interface design: PSR-18 + convenience methods

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
