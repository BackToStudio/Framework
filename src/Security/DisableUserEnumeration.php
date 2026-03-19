<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\RequestContextInterface;
use BackTo\Framework\Contracts\ResponseEmitterInterface;
use BackTo\Framework\Contracts\UserContextInterface;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;

class DisableUserEnumeration implements Hooks, SecurityRuleInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly RequestContextInterface $requestContext;
    private readonly ResponseEmitterInterface $responseEmitter;
    private readonly UserContextInterface $userContext;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        RequestContextInterface $requestContext,
        ResponseEmitterInterface $responseEmitter,
        UserContextInterface $userContext,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->requestContext = $requestContext;
        $this->responseEmitter = $responseEmitter;
        $this->userContext = $userContext;
    }

    public function getName(): string
    {
        return 'disable_user_enumeration';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('init', [$this, 'blockAuthorQuery']);
        $this->hookDispatcher->addFilter('rest_endpoints', [$this, 'restrictUserEndpoints']);
    }

    public function blockAuthorQuery(): void
    {
        if ($this->hookDispatcher->isAdmin()) {
            return;
        }

        if ($this->requestContext->query('author') !== '' || $this->requestContext->query('author_name') !== '') {
            $this->redirectToHome();
        }
    }

    protected function redirectToHome(): void
    {
        $this->responseEmitter->setStatusCode(301);
        $this->responseEmitter->sendHeader('Location: /');
        $this->responseEmitter->terminate();
    }

    /**
     * Remove /wp/v2/users endpoint for non-authenticated requests.
     *
     * @param array<string, mixed> $endpoints
     * @return array<string, mixed>
     */
    public function restrictUserEndpoints(array $endpoints): array
    {
        if ($this->isUserLoggedIn()) {
            return $endpoints;
        }

        $protectedRoutes = [
            '/wp/v2/users',
            '/wp/v2/users/(?P<id>[\d]+)',
        ];

        foreach ($protectedRoutes as $route) {
            unset($endpoints[$route]);
        }

        return $endpoints;
    }

    protected function isUserLoggedIn(): bool
    {
        return $this->userContext->isLoggedIn();
    }
}
