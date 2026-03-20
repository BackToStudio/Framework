# Implement automatic retry with exponential backoff

Wrap the HTTP client to retry transient failures:

```php
<?php

namespace MyPlugin\Http;

use BackTo\Framework\Http\Contracts\HttpClientInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Log\LoggerInterface;

final class RetryingHttpClient
{
    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
        private readonly int $maxRetries = 3,
    ) {}

    public function getWithRetry(string $url, array $options = []): ResponseInterface
    {
        $attempt = 0;

        do {
            $response = $this->httpClient->get($url, $options);
            $status = $response->getStatusCode();

            // Success or client error (don't retry 4xx)
            if ($status > 0 && $status < 500) {
                return $response;
            }

            $attempt++;
            if ($attempt <= $this->maxRetries) {
                $delay = (int) pow(2, $attempt); // 2s, 4s, 8s
                $this->logger->warning('HTTP request failed, retrying in {delay}s', [
                    'url'     => $url,
                    'status'  => $status,
                    'attempt' => $attempt,
                    'delay'   => $delay,
                ]);
                sleep($delay);
            }
        } while ($attempt <= $this->maxRetries);

        return $response; // Return last failed response
    }
}
```
