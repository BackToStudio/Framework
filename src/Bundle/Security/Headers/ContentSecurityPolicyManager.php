<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Headers;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Contracts\ResponseEmitterInterface;
use BackTo\Framework\Bundle\Security\Contracts\ContentSecurityPolicyInterface;
use BackTo\Framework\Bundle\Security\Contracts\SecurityRuleInterface;

final class ContentSecurityPolicyManager implements Hooks, SecurityRuleInterface, ContentSecurityPolicyInterface
{
    private readonly HookDispatcherInterface $hookDispatcher;
    private readonly CspDirectiveBuilder $directiveBuilder;
    private readonly CspHeaderSender $headerSender;
    private readonly CspScriptTagModifier $scriptTagModifier;

    public function __construct(
        HookDispatcherInterface $hookDispatcher,
        ResponseEmitterInterface $responseEmitter,
        bool $reportOnly = false,
    ) {
        $this->hookDispatcher = $hookDispatcher;
        $this->directiveBuilder = new CspDirectiveBuilder();
        $this->headerSender = new CspHeaderSender($responseEmitter, $this->directiveBuilder, $reportOnly);
        $this->scriptTagModifier = new CspScriptTagModifier($this->headerSender);
    }

    public function getName(): string
    {
        return 'content_security_policy';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('send_headers', [$this, 'sendCspHeader']);
        $this->hookDispatcher->addFilter('script_loader_tag', [$this, 'addNonceToScripts'], 10, 2);
    }

    public function addDirective(string $directive, string|array $value): self
    {
        $this->directiveBuilder->addDirective($directive, $value);

        return $this;
    }

    /**
     * @return array<string, string[]>
     */
    public function getDirectives(): array
    {
        return $this->directiveBuilder->getDirectives();
    }

    public function buildHeaderValue(): string
    {
        return $this->directiveBuilder->buildHeaderValue($this->headerSender->getNonce());
    }

    public function generateNonce(): string
    {
        return $this->headerSender->generateNonce();
    }

    public function getNonce(): string
    {
        return $this->headerSender->getNonce();
    }

    public function sendCspHeader(): void
    {
        $this->headerSender->sendCspHeader();
    }

    public function addNonceToScripts(string $tag, string $handle): string
    {
        return $this->scriptTagModifier->addNonceToScripts($tag, $handle);
    }

    public function isReportOnly(): bool
    {
        return $this->headerSender->isReportOnly();
    }
}
