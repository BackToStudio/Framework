<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Contracts;

/**
 * Port interface for Content Security Policy management.
 */
interface ContentSecurityPolicyInterface
{
    
    public function addDirective(string $directive, string|array $value): self;

    /**
     * @return array<string, string[]>
     */
    public function getDirectives(): array;

    public function buildHeaderValue(): string;

    public function generateNonce(): string;

    public function getNonce(): string;
}
