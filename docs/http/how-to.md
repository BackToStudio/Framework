# HTTP Client — How-to guides

*How-to — Task-oriented*

Practical recipes for common HTTP client use cases.

---

## Set a request timeout

Pass `timeout` in the options array (seconds):

```php
$response = $this->httpClient->get('https://api.example.com/slow-endpoint', [
    'timeout' => 5,
]);
```

Default timeout is determined by WordPress (typically 5 seconds).

---

## Send a non-blocking (fire-and-forget) request

Set `blocking` to `false`. The request is sent but execution continues immediately without waiting for the response:

```php
$this->httpClient->get('https://example.com/webhook', [
    'blocking' => false,
    'timeout'  => 30,
]);
// Execution continues immediately
```

The returned `ResponseInterface` will have a `0` status code and empty body since WordPress does not wait for the response.

---

## Send custom headers

```php
$response = $this->httpClient->get('https://api.example.com/data', [
    'headers' => [
        'Authorization' => 'Bearer ' . $apiKey,
        'Accept'        => 'application/json',
        'X-Request-ID'  => wp_generate_uuid4(),
    ],
]);
```

---

## Post a JSON payload

```php
$response = $this->httpClient->post('https://api.example.com/events', [
    'headers' => ['Content-Type' => 'application/json'],
    'body'    => json_encode([
        'event' => 'page_viewed',
        'url'   => home_url('/'),
    ]),
]);
```

---

## Post form data

```php
$response = $this->httpClient->post('https://api.example.com/form', [
    'body' => [
        'name'  => 'John',
        'email' => 'john@example.com',
    ],
]);
```

When `body` is an array, WordPress sends it as `application/x-www-form-urlencoded`.

---

## Handle errors

Check the status code. A `0` status code indicates a network-level error (DNS failure, timeout, SSL error):

```php
$response = $this->httpClient->get('https://api.example.com/data');

if ($response->getStatusCode() === 0) {
    // Network error — body contains the error message
    $error = (string) $response->getBody();
    $this->logger->error('HTTP request failed: {error}', ['error' => $error]);
    return;
}

if ($response->getStatusCode() >= 400) {
    // HTTP error (4xx, 5xx)
    $this->logger->warning('API returned {code}', [
        'code' => $response->getStatusCode(),
        'body' => (string) $response->getBody(),
    ]);
    return;
}

// Success
$data = json_decode((string) $response->getBody(), true);
```

---

## Disable SSL verification (development only)

```php
$response = $this->httpClient->get('https://localhost:8443/api', [
    'sslverify' => false,
]);
```

> **Warning:** Never disable SSL verification in production.

---

## Use the PSR-18 interface with third-party libraries

Any library that accepts `Psr\Http\Client\ClientInterface` can use the framework's HTTP client directly:

```php
use Psr\Http\Client\ClientInterface;

class ExternalSdkAdapter
{
    public function __construct(
        private readonly ClientInterface $httpClient, // PSR-18
    ) {}
}
```

The framework's `HttpClientInterface` extends PSR-18, so it satisfies both type hints.

---

## Swap the HTTP client implementation

To replace the WordPress adapter with a different HTTP client (e.g., for testing or non-WordPress environments), override the DI binding:

```php
use BackTo\Framework\Http\Contracts\HttpClientInterface;

$containerBuilder->register(HttpClientInterface::class, MyCustomHttpClient::class);
```

Your implementation must implement `HttpClientInterface` (which extends `Psr\Http\Client\ClientInterface`).

---

## Test code that uses the HTTP client

Mock `HttpClientInterface` in unit tests:

```php
use BackTo\Framework\Http\Contracts\HttpClientInterface;
use BackTo\Framework\Http\Response;

$httpClient = $this->createMock(HttpClientInterface::class);
$httpClient->method('get')->willReturn(new Response(200, [], '{"ok":true}'));

$service = new MyService($httpClient);
```

The framework's `Response` class implements PSR-7 and can be instantiated directly in tests:

```php
use BackTo\Framework\Http\Response;

// Simple response
new Response(200);

// With body
new Response(200, [], '{"data": "value"}');

// With headers
new Response(200, ['Content-Type' => 'application/json'], '{}');

// Error response
new Response(500, [], 'Internal Server Error');
```
