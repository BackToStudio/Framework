<?php

declare(strict_types=1);

namespace BackTo\Framework\Http;

use BackTo\Framework\Http\Contracts\HttpClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Decorator that adds JSON convenience methods.
 *
 * When a 'json' key is present in options, it is encoded to a JSON body
 * and the Content-Type header is set automatically. Also provides
 * a decodeJson() helper for response parsing.
 */
final class JsonHttpClient implements HttpClientInterface
{
    private readonly HttpClientInterface $inner;

    public function __construct(HttpClientInterface $inner)
    {
        $this->inner = $inner;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        return $this->inner->sendRequest($request);
    }

    public function get(string $url, array $options = []): ResponseInterface
    {
        return $this->inner->get($url, $options);
    }

    public function post(string $url, array $options = []): ResponseInterface
    {
        return $this->inner->post($url, self::prepareJsonOptions($options));
    }

    public function request(string $method, string $url, array $options = []): ResponseInterface
    {
        return $this->inner->request($method, $url, self::prepareJsonOptions($options));
    }

    /**
     * Decode a JSON response body.
     *
     * @return array<string, mixed>
     * @throws \JsonException If the body is not valid JSON.
     */
    public static function decodeJson(ResponseInterface $response): array
    {
        $body = (string) $response->getBody();

        if ($body === '') {
            return [];
        }

        /** @var array<string, mixed> */
        return json_decode($body, true, 512, \JSON_THROW_ON_ERROR);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private static function prepareJsonOptions(array $options): array
    {
        if (!isset($options['json'])) {
            return $options;
        }

        $options['body'] = json_encode($options['json'], \JSON_THROW_ON_ERROR);
        $options['headers'] = array_merge($options['headers'] ?? [], [
            'Content-Type' => 'application/json',
            'Accept' => 'application/json',
        ]);
        unset($options['json']);

        return $options;
    }
}
