<?php

declare(strict_types=1);

namespace BackTo\Framework\Http\Infrastructure;

use BackTo\Framework\Http\Contracts\HttpClientInterface;
use BackTo\Framework\Http\Contracts\HttpResponseInterface;
use BackTo\Framework\Http\HttpResponse;

/**
 * WordPress adapter for HTTP client operations.
 *
 * Wraps wp_remote_request(), wp_remote_get(), wp_remote_post()
 * and converts WP responses into HttpResponseInterface.
 */
final class WordPressHttpClient implements HttpClientInterface
{
    public function request(string $method, string $url, array $options = []): HttpResponseInterface
    {
        $args = $this->buildArgs($method, $options);

        $response = \wp_remote_request($url, $args);

        return $this->toResponse($response);
    }

    public function get(string $url, array $options = []): HttpResponseInterface
    {
        return $this->request('GET', $url, $options);
    }

    public function post(string $url, array $options = []): HttpResponseInterface
    {
        return $this->request('POST', $url, $options);
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
     * @param array<string, mixed>|\WP_Error $response
     */
    private function toResponse(mixed $response): HttpResponseInterface
    {
        if ($response instanceof \WP_Error) {
            return new HttpResponse(0, $response->get_error_message());
        }

        $statusCode = (int) \wp_remote_retrieve_response_code($response);
        $body = (string) \wp_remote_retrieve_body($response);
        $rawHeaders = \wp_remote_retrieve_headers($response);

        $headers = [];
        if ($rawHeaders instanceof \WpOrg\Requests\Utility\CaseInsensitiveDictionary || is_iterable($rawHeaders)) {
            foreach ($rawHeaders as $name => $value) {
                $headers[(string) $name] = is_array($value) ? $value : [(string) $value];
            }
        }

        return new HttpResponse($statusCode, $body, $headers);
    }
}
