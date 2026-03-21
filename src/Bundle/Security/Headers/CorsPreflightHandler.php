<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Headers;

use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Contracts\ResponseEmitterInterface;

/**
 * Handles CORS preflight (OPTIONS) requests.
 *
 * Single responsibility: detect preflight requests, send headers, and terminate.
 */
class CorsPreflightHandler
{
    private readonly RequestContextInterface $requestContext;
    private readonly ResponseEmitterInterface $responseEmitter;
    private readonly CorsOriginValidator $originValidator;
    private readonly CorsHeaderWriter $headerWriter;

    public function __construct(
        RequestContextInterface $requestContext,
        ResponseEmitterInterface $responseEmitter,
        CorsOriginValidator $originValidator,
        CorsHeaderWriter $headerWriter,
    ) {
        $this->requestContext = $requestContext;
        $this->responseEmitter = $responseEmitter;
        $this->originValidator = $originValidator;
        $this->headerWriter = $headerWriter;
    }

    public function handleCors(): void
    {
        $origin = $this->getRequestOrigin();

        if ($origin === '' || !$this->originValidator->isOriginAllowed($origin)) {
            return;
        }

        if ($this->isPreflightRequest()) {
            $this->headerWriter->sendHeaders($origin);
            $this->exitPreflight();
        }
    }

    protected function getRequestOrigin(): string
    {
        return $this->requestContext->server('HTTP_ORIGIN');
    }

    protected function isPreflightRequest(): bool
    {
        return $this->requestContext->getMethod() === 'OPTIONS';
    }

    protected function exitPreflight(): void
    {
        $this->responseEmitter->setStatusCode(200);
        $this->responseEmitter->terminate();
    }
}
