# Swap the HTTP client implementation

To replace the WordPress adapter with a different HTTP client (e.g., for testing or non-WordPress environments), override the DI binding:

```php
use BackTo\Framework\Http\Contracts\HttpClientInterface;

$containerBuilder->register(HttpClientInterface::class, MyCustomHttpClient::class);
```

Your implementation must implement `HttpClientInterface` (which extends `Psr\Http\Client\ClientInterface`).
