<?php

declare(strict_types=1);

namespace BackTo\Framework\RestApi\Contracts;

/**
 * Framework-level REST response abstraction.
 *
 * Decouples route handlers from WP_REST_Response so that domain
 * code never references WordPress types directly.
 *
 * The infrastructure adapter converts this to a WP_REST_Response
 * before WordPress processes it.
 */
final class RestResponse
{
    /** @var array<string, mixed>|mixed */
    private readonly mixed $data;

    private readonly int $statusCode;

    /** @var array<string, string> */
    private readonly array $headers;

    /**
     * @param array<string, string> $headers
     */
    public function __construct(
        mixed $data = [],
        int $statusCode = 200,
        array $headers = [],
    ) {
        $this->data = $data;
        $this->statusCode = $statusCode;
        $this->headers = $headers;
    }

    public function getData(): mixed
    {
        return $this->data;
    }

    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }
}
