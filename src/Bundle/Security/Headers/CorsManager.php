<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Headers;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Contracts\ResponseEmitterInterface;
use BackTo\Framework\Bundle\Security\Contracts\CorsManagerInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;

/**
 * Fine-grained CORS header management for headless/SPA WordPress setups.
 *
 * Thin orchestrator that delegates to CorsOriginValidator, CorsHeaderWriter,
 * and CorsPreflightHandler.
 */
class CorsManager implements Hooks, SecurityRuleInterface, CorsManagerInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly RequestContextInterface $requestContext;
    private readonly ResponseEmitterInterface $responseEmitter;
    private readonly CorsOriginValidator $originValidator;
    private readonly CorsHeaderWriter $headerWriter;
    private readonly CorsPreflightHandler $preflightHandler;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        RequestContextInterface $requestContext,
        ResponseEmitterInterface $responseEmitter,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->requestContext = $requestContext;
        $this->responseEmitter = $responseEmitter;
        $this->originValidator = new CorsOriginValidator();
        $this->headerWriter = new CorsHeaderWriter($responseEmitter, $this->originValidator);
        $this->preflightHandler = new CorsPreflightHandler(
            $requestContext,
            $responseEmitter,
            $this->originValidator,
            $this->headerWriter,
        );
    }

    public function getName(): string
    {
        return 'cors_manager';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('rest_api_init', [$this, 'handleCors'], 5);
        $this->hookDispatcher->addFilter('rest_pre_serve_request', [$this, 'sendCorsHeaders'], 10, 1);
    }

    public function addAllowedOrigin(string|array $origin): self
    {
        $this->originValidator->addAllowedOrigin($origin);

        return $this;
    }

    public function addAllowedMethod(string|array $method): self
    {
        $this->headerWriter->addAllowedMethod($method);

        return $this;
    }

    public function addAllowedHeader(string|array $header): self
    {
        $this->headerWriter->addAllowedHeader($header);

        return $this;
    }

    public function setAllowCredentials(bool $allow): self
    {
        $this->originValidator->setAllowCredentials($allow);

        return $this;
    }

    public function setMaxAge(int $seconds): self
    {
        $this->headerWriter->setMaxAge($seconds);

        return $this;
    }

    /**
     * @return array<string, string>
     */
    public function buildHeaders(string $requestOrigin): array
    {
        return $this->headerWriter->buildHeaders($requestOrigin);
    }

    public function handleCors(): void
    {
        $this->preflightHandler->handleCors();
    }

    public function sendCorsHeaders(bool $served): bool
    {
        $origin = $this->getRequestOrigin();

        if ($origin !== '' && $this->originValidator->isOriginAllowed($origin)) {
            $this->sendHeaders($origin);
        }

        return $served;
    }

    public function getAllowedOrigins(): array
    {
        return $this->originValidator->getAllowedOrigins();
    }

    public function getAllowedMethods(): array
    {
        return $this->headerWriter->getAllowedMethods();
    }

    public function getAllowedHeaders(): array
    {
        return $this->headerWriter->getAllowedHeaders();
    }

    public function isAllowCredentials(): bool
    {
        return $this->originValidator->isAllowCredentials();
    }

    public function getMaxAge(): int
    {
        return $this->headerWriter->getMaxAge();
    }

    protected function sendHeaders(string $origin): void
    {
        $this->headerWriter->sendHeaders($origin);
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
