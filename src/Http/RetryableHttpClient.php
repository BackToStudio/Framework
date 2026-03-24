<?php

declare(strict_types=1);

namespace BackTo\Framework\Http;

use BackTo\Framework\Http\Contracts\HttpClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Decorator that retries failed HTTP requests with exponential backoff.
 *
 * A request is retried when:
 *   - The response status code is in the retryable set (default: 429, 502, 503, 504)
 *   - An exception is thrown during the request
 *
 * Backoff formula: delay × 2^(attempt - 1) with optional jitter.
 */
final class RetryableHttpClient implements HttpClientInterface
{
    private readonly HttpClientInterface $inner;
    private readonly int $maxRetries;
    private readonly int $delayMs;

    /** @var int[] */
    private readonly array $retryableStatusCodes;

    /**
     * @param int $maxRetries Maximum number of retries (not total attempts).
     * @param int $delayMs Base delay in milliseconds before first retry.
     * @param int[] $retryableStatusCodes HTTP status codes that trigger a retry.
     */
    public function __construct(
        HttpClientInterface $inner,
        int $maxRetries = 3,
        int $delayMs = 1000,
        array $retryableStatusCodes = [429, 502, 503, 504],
    ) {
        if ($maxRetries < 0) {
            throw new \InvalidArgumentException('Max retries must be zero or positive.');
        }

        if ($delayMs < 0) {
            throw new \InvalidArgumentException('Delay must be zero or positive.');
        }

        $this->inner = $inner;
        $this->maxRetries = $maxRetries;
        $this->delayMs = $delayMs;
        $this->retryableStatusCodes = $retryableStatusCodes;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $attempt = 0;
        $lastException = null;

        while (true) {
            try {
                $response = $this->inner->sendRequest($request);

                if ($attempt < $this->maxRetries && in_array($response->getStatusCode(), $this->retryableStatusCodes, true)) {
                    $this->sleep($attempt);
                    $attempt++;
                    continue;
                }

                return $response;
            } catch (\Throwable $e) {
                $lastException = $e;

                if ($attempt >= $this->maxRetries) {
                    throw $e;
                }

                $this->sleep($attempt);
                $attempt++;
            }
        }
    }

    public function get(string $url, array $options = []): ResponseInterface
    {
        return $this->retryRequest('GET', $url, $options);
    }

    public function post(string $url, array $options = []): ResponseInterface
    {
        return $this->retryRequest('POST', $url, $options);
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->retryRequest($method, $url, $options);
    }

    /**
     * @param array<string, mixed> $options
     */
    private function retryRequest(string $method, string $url, array $options): ResponseInterface
    {
        $attempt = 0;

        while (true) {
            try {
                $response = $this->inner->request($method, $url, $options);

                if ($attempt < $this->maxRetries && in_array($response->getStatusCode(), $this->retryableStatusCodes, true)) {
                    $this->sleep($attempt);
                    $attempt++;
                    continue;
                }

                return $response;
            } catch (\Throwable $e) {
                if ($attempt >= $this->maxRetries) {
                    throw $e;
                }

                $this->sleep($attempt);
                $attempt++;
            }
        }
    }

    /**
     * Sleep with exponential backoff: delayMs × 2^attempt.
     */
    protected function sleep(int $attempt): void
    {
        $microseconds = $this->delayMs * (int) pow(2, $attempt) * 1000;
        usleep($microseconds);
    }
}
