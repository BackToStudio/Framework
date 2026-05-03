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
 * Only idempotent methods are retried by default (GET, HEAD, OPTIONS).
 * Backoff formula: delay × 2^(attempt - 1).
 */
final class RetryableHttpClient implements HttpClientInterface
{
    private readonly HttpClientInterface $inner;
    private readonly int $maxRetries;
    private readonly int $delayMs;

    /** @var int[] */
    private readonly array $retryableStatusCodes;

    /** @var string[] HTTP methods safe to retry (idempotent) */
    private readonly array $retryableMethods;

    /** @var callable(int): void */
    private $sleepFn;

    /**
     * @param int $maxRetries Maximum number of retries (not total attempts).
     * @param int $delayMs Base delay in milliseconds before first retry.
     * @param int[] $retryableStatusCodes HTTP status codes that trigger a retry.
     * @param string[] $retryableMethods HTTP methods safe to retry (default: idempotent only).
     * @param callable(int): void|null $sleepFn Custom sleep function (for testing).
     */
    public function __construct(
        HttpClientInterface $inner,
        int $maxRetries = 3,
        int $delayMs = 1000,
        array $retryableStatusCodes = [429, 502, 503, 504],
        array $retryableMethods = ['GET', 'HEAD', 'OPTIONS'],
        ?callable $sleepFn = null,
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
        $this->retryableMethods = array_map('strtoupper', $retryableMethods);
        $this->sleepFn = $sleepFn ?? static function (int $microseconds): void {
            usleep($microseconds);
        };
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $method = strtoupper($request->getMethod());

        return $this->retry($method, fn (): ResponseInterface => $this->inner->sendRequest($request));
    }

    public function get(string $url, array $options = []): ResponseInterface
    {
        return $this->request('GET', $url, $options);
    }

    public function post(string $url, array $options = []): ResponseInterface
    {
        return $this->request('POST', $url, $options);
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->retry(
            strtoupper($method),
            fn (): ResponseInterface => $this->inner->request($method, $url, $options),
        );
    }

    /**
     * Execute a request with retry logic.
     *
     * @param callable(): ResponseInterface $attempt
     */
    private function retry(string $method, callable $attempt): ResponseInterface
    {
        $canRetry = in_array($method, $this->retryableMethods, true);
        $retryCount = 0;

        while (true) {
            try {
                $response = $attempt();

                if ($canRetry && $retryCount < $this->maxRetries && in_array($response->getStatusCode(), $this->retryableStatusCodes, true)) {
                    $this->sleep($retryCount);
                    $retryCount++;
                    continue;
                }

                return $response;
            } catch (\Throwable $e) {
                if (!$canRetry || $retryCount >= $this->maxRetries) {
                    throw $e;
                }

                $this->sleep($retryCount);
                $retryCount++;
            }
        }
    }

    private function sleep(int $attempt): void
    {
        $microseconds = $this->delayMs * (int) pow(2, $attempt) * 1000;
        ($this->sleepFn)($microseconds);
    }
}
