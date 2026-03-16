<?php

declare(strict_types=1);

namespace BackTo\Framework\Security;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Security\Contracts\SecurityRuleInterface;
use BackTo\Framework\Security\Contracts\SubresourceIntegrityInterface;

/**
 * Adds Subresource Integrity (SRI) hashes to external scripts and styles.
 *
 * Protects against CDN supply chain attacks by verifying that fetched
 * resources match their expected content hash.
 *
 * Usage:
 *   $sri->registerHash('jquery', 'sha384-abc123...');
 *   // Automatically adds integrity="sha384-abc123..." crossorigin="anonymous" to the tag
 */
class SubresourceIntegrity implements Hooks, SecurityRuleInterface, SubresourceIntegrityInterface
{
    private HookDispatcherInterface $hookDispatcher;

    /** @var array<string, string> handle => hash */
    private array $hashes = [];

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function getName(): string
    {
        return 'subresource_integrity';
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter('script_loader_tag', [$this, 'addIntegrityToScript'], 20, 2);
        $this->hookDispatcher->addFilter('style_loader_tag', [$this, 'addIntegrityToStyle'], 20, 2);
    }

    public function registerHash(string $handle, string $hash): self
    {
        $this->hashes[$handle] = $hash;

        return $this;
    }

    public function getHash(string $handle): ?string
    {
        return $this->hashes[$handle] ?? null;
    }

    public function addIntegrityToScript(string $tag, string $handle): string
    {
        return $this->addIntegrityAttribute($tag, $handle, '<script ');
    }

    public function addIntegrityToStyle(string $tag, string $handle): string
    {
        return $this->addIntegrityAttribute($tag, $handle, '<link ');
    }

    /**
     * @return array<string, string>
     */
    public function getRegisteredHashes(): array
    {
        return $this->hashes;
    }

    private function addIntegrityAttribute(string $tag, string $handle, string $tagPrefix): string
    {
        $hash = $this->hashes[$handle] ?? null;

        if ($hash === null) {
            return $tag;
        }

        // Already has integrity attribute
        if (str_contains($tag, 'integrity=')) {
            return $tag;
        }

        $integrityAttr = 'integrity="' . $hash . '" crossorigin="anonymous" ';

        return str_replace($tagPrefix, $tagPrefix . $integrityAttr, $tag);
    }
}
