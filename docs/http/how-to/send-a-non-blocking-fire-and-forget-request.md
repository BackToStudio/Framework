# Send a non-blocking (fire-and-forget) request

Set `blocking` to `false`. The request is sent but execution continues immediately without waiting for the response:

```php
$this->httpClient->get('https://example.com/webhook', [
    'blocking' => false,
    'timeout'  => 30,
]);
// Execution continues immediately
```

The returned `ResponseInterface` will have a `0` status code and empty body since WordPress does not wait for the response.
