# Log requests and responses safely

Create a logging decorator that redacts sensitive headers:

```php
<?php

namespace MyPlugin\Http;

use BackTo\Framework\Http\Contracts\HttpClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class LoggingHttpClient
{
    private const REDACTED_HEADERS = ['Authorization', 'X-API-Key', 'Cookie'];

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
    ) {}

    public function get(string $url, array $options = []): ResponseInterface
    {
        $this->logRequest('GET', $url, $options);
        $response = $this->httpClient->get($url, $options);
        $this->logResponse('GET', $url, $response);
        return $response;
    }

    public function post(string $url, array $options = []): ResponseInterface
    {
        $this->logRequest('POST', $url, $options);
        $response = $this->httpClient->post($url, $options);
        $this->logResponse('POST', $url, $response);
        return $response;
    }

    private function logRequest(string $method, string $url, array $options): void
    {
        $headers = $options['headers'] ?? [];
        foreach (self::REDACTED_HEADERS as $name) {
            if (isset($headers[$name])) {
                $headers[$name] = '***REDACTED***';
            }
        }

        $this->logger->debug('HTTP {method} {url}', [
            'method'  => $method,
            'url'     => $url,
            'headers' => $headers,
        ]);
    }

    private function logResponse(string $method, string $url, ResponseInterface $response): void
    {
        $this->logger->debug('HTTP {method} {url} → {status}', [
            'method' => $method,
            'url'    => $url,
            'status' => $response->getStatusCode(),
        ]);
    }
}
```
