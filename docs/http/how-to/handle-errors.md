# Handle errors

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
