<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo;

use BackTo\Framework\Bundle\Seo\Contracts\SeoProviderInterface;

/**
 * Central manager that resolves the active SEO provider.
 *
 * Iterates over registered providers and returns the first active one.
 */
final class SeoManager
{
    /** @var SeoProviderInterface[] */
    private array $providers;

    private ?SeoProviderInterface $resolved = null;
    private bool $wasResolved = false;

    
    public function __construct(array $providers = [])
    {
        $this->providers = $providers;
    }

    public function addProvider(SeoProviderInterface $provider): self
    {
        $this->providers[] = $provider;
        $this->wasResolved = false;

        return $this;
    }

    /**
     * Return the active SEO provider, or null if none is active.
     */
    public function getProvider(): ?SeoProviderInterface
    {
        if (!$this->wasResolved) {
            $this->resolved = $this->resolveProvider();
            $this->wasResolved = true;
        }

        return $this->resolved;
    }

    /**
     * Whether an SEO plugin is available and active.
     */
    public function hasProvider(): bool
    {
        return $this->getProvider() !== null;
    }

    /**
     * Shorthand: get social links from the active provider.
     *
     * @return array<string, string|null>
     */
    public function getSocialLinks(): array
    {
        $provider = $this->getProvider();

        return $provider !== null ? $provider->getSocialLinks() : [];
    }

    private function resolveProvider(): ?SeoProviderInterface
    {
        foreach ($this->providers as $provider) {
            if ($provider->isActive()) {
                return $provider;
            }
        }

        return null;
    }
}
