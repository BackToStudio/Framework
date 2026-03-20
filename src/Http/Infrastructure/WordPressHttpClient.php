<?php

declare(strict_types=1);

namespace BackTo\Framework\Http\Infrastructure;

use BackTo\Framework\Http\Contracts\HttpClientInterface;
use Nyholm\Psr7\Request;
use Nyholm\Psr7\Response;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * WordPress adapter implementing PSR-18 + convenience methods.
 *
 * Wraps wp_remote_request() and converts WP responses into PSR-7.
 */
final class WordPressHttpClient implements HttpClientInterface
{
    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $args = [
            'method'  => $request->getMethod(),
            'headers' => [],
            'body'    => (string) $request->getBody(),
        ];

        foreach ($request->getHeaders() as $name => $values) {
            $args['headers'][$name] = implode(', ', $values);
        }

        $wpResponse = \wp_remote_request((string) $request->getUri(), $args);

        return $this->toResponse($wpResponse);
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
        $args = $this->buildArgs($method, $options);

        $wpResponse = \wp_remote_request($url, $args);

        return $this->toResponse($wpResponse);
    }

    /**
     * @param array<string, mixed> $options
     * @return array<string, mixed>
     */
    private function buildArgs(string $method, array $options): array
    {
        $args = ['method' => $method];

        if (isset($options['timeout'])) {
            $args['timeout'] = (int) $options['timeout'];
        }

        if (isset($options['headers'])) {
            $args['headers'] = (array) $options['headers'];
        }

        if (isset($options['body'])) {
            $args['body'] = $options['body'];
        }

        if (isset($options['blocking'])) {
            $args['blocking'] = (bool) $options['blocking'];
        }

        if (isset($options['sslverify'])) {
            $args['sslverify'] = (bool) $options['sslverify'];
        }

        if (isset($options['cookies'])) {
            $args['cookies'] = (array) $options['cookies'];
        }

        return $args;
    }

    /**
     * @param array<string, mixed>|\WP_Error $wpResponse
     */
    private function toResponse(mixed $wpResponse): ResponseInterface
    {
        if ($wpResponse instanceof \WP_Error) {
            return new Response(0, [], $wpResponse->get_error_message());
        }

        $statusCode = (int) \wp_remote_retrieve_response_code($wpResponse);
        $body = (string) \wp_remote_retrieve_body($wpResponse);
        $rawHeaders = \wp_remote_retrieve_headers($wpResponse);

        $headers = [];
        if (is_iterable($rawHeaders)) {
            foreach ($rawHeaders as $name => $value) {
                $headers[(string) $name] = is_array($value) ? $value : [(string) $value];
            }
        }

        return new Response($statusCode, $headers, $body);
    }
}
