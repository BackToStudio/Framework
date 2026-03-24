<?php

declare(strict_types=1);

namespace BackTo\Framework\Http\Exception;

use Psr\Http\Message\ResponseInterface;

/**
 * Thrown when an HTTP response indicates an error (4xx/5xx).
 */
class HttpException extends \RuntimeException
{
    private readonly ResponseInterface $response;

    public function __construct(ResponseInterface $response, string $message = '', ?\Throwable $previous = null)
    {
        if ($message === '') {
            $message = sprintf('HTTP %d: %s', $response->getStatusCode(), $response->getReasonPhrase());
        }

        parent::__construct($message, $response->getStatusCode(), $previous);

        $this->response = $response;
    }

    public function getResponse(): ResponseInterface
    {
        return $this->response;
    }
}
