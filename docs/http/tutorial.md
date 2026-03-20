# Getting started with the HTTP Client

*Tutorial — Learning-oriented*

This tutorial walks you through sending HTTP requests using the framework's HTTP client. By the end, you'll know how to make GET and POST requests, read responses, and use the PSR-18 standard interface.

## Prerequisites

- A WordPress plugin or theme using the BackTo Framework
- The framework's DI container configured (the HTTP client is auto-wired)

## Step 1: Inject the HTTP client

The framework registers `HttpClientInterface` in the DI container. Inject it via constructor:

```php
use BackTo\Framework\Http\Contracts\HttpClientInterface;

class MyService
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
    ) {}
}
```

## Step 2: Send a GET request

Use the `get()` convenience method. It returns a PSR-7 `ResponseInterface`:

```php
$response = $this->httpClient->get('https://api.example.com/posts');

$statusCode = $response->getStatusCode(); // 200
$body = (string) $response->getBody();    // '{"posts": [...]}'
```

## Step 3: Read the response

The response implements `Psr\Http\Message\ResponseInterface`:

```php
// Status
$response->getStatusCode();    // 200
$response->getReasonPhrase();  // "OK"

// Headers
$response->hasHeader('Content-Type');           // true
$response->getHeaderLine('Content-Type');       // "application/json"
$response->getHeader('Content-Type');           // ["application/json"]

// Body (PSR-7 StreamInterface)
$body = (string) $response->getBody();          // full body as string
$body = $response->getBody()->getContents();    // remaining stream contents
```

## Step 4: Send a POST request

```php
$response = $this->httpClient->post('https://api.example.com/posts', [
    'headers' => [
        'Content-Type'  => 'application/json',
        'Authorization' => 'Bearer ' . $token,
    ],
    'body' => json_encode(['title' => 'Hello World']),
]);

if ($response->getStatusCode() === 201) {
    $created = json_decode((string) $response->getBody(), true);
}
```

## Step 5: Use any HTTP method

The `request()` method accepts any HTTP verb:

```php
$response = $this->httpClient->request('PUT', 'https://api.example.com/posts/42', [
    'body'    => json_encode(['title' => 'Updated']),
    'headers' => ['Content-Type' => 'application/json'],
]);

$response = $this->httpClient->request('DELETE', 'https://api.example.com/posts/42');
```

## Step 6: Use the PSR-18 standard interface

If you already have PSR-7 `RequestInterface` objects (from Guzzle, nyholm/psr7, etc.), use the standard `sendRequest()` method:

```php
use Nyholm\Psr7\Request;

$request = new Request('GET', 'https://api.example.com/posts');
$response = $this->httpClient->sendRequest($request);
```

This is the standard PSR-18 method. Any library that produces PSR-7 requests is compatible.

## Next steps

- See [Common tasks](how-to/README.md) for practical recipes (timeouts, non-blocking requests, error handling)
- See [API reference](reference/README.md) for the complete interface documentation
