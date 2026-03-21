<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Security\Headers;

/**
 * Modifies script tags to add CSP nonce attributes.
 *
 * Single responsibility: given a nonce, inject it into script tags
 * that don't already have one.
 */
final class CspScriptTagModifier
{
    private readonly CspHeaderSender $headerSender;

    public function __construct(CspHeaderSender $headerSender)
    {
        $this->headerSender = $headerSender;
    }

    public function addNonceToScripts(string $tag, string $handle): string
    {
        $nonce = $this->headerSender->getNonce();

        if ($nonce === '') {
            return $tag;
        }

        if (str_contains($tag, 'nonce=')) {
            return $tag;
        }

        return str_replace('<script ', '<script nonce="' . htmlspecialchars($nonce, ENT_QUOTES, 'UTF-8') . '" ', $tag);
    }
}
