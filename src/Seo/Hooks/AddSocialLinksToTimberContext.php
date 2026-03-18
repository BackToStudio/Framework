<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Hooks;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Seo\SeoManager;

/**
 * Add social links from the active SEO plugin to the Timber context.
 */
final class AddSocialLinksToTimberContext implements Hooks
{
    private readonly SeoManager $seoManager;

    private readonly HookDispatcherInterface $hookDispatcher;

    public function __construct(SeoManager $seoManager, HookDispatcherInterface $hookDispatcher)
    {
        $this->seoManager = $seoManager;
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter('timber/context', [$this, 'addSocialLinks']);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function addSocialLinks(array $context): array
    {
        if (!$this->seoManager->hasProvider()) {
            return $context;
        }

        $links = $this->seoManager->getSocialLinks();

        foreach ($links as $network => $url) {
            $context[$network] = $url;
        }

        return $context;
    }
}
