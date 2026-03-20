# Test code that uses the HTTP client


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
