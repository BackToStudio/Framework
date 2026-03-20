# DI Registration


The `HttpClientInterface` binding is registered in `HooksExtension`:

```php
$container->register(HttpClientInterface::class, WordPressHttpClient::class);
```

Autowiring resolves both `HttpClientInterface` and `Psr\Http\Client\ClientInterface` to the same instance.
