# Use the PSR-18 interface with third-party libraries

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
