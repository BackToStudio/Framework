<?php

namespace BackTo\Framework\Seo\Hooks;

use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Seo\SeoManager;

/**
 * Add social links from the active SEO plugin to the Timber context.
 */
class AddSocialLinksToTimberContext implements Hooks
{
    private SeoManager $seoManager;

    public function __construct(SeoManager $seoManager)
    {
        $this->seoManager = $seoManager;
    }

    public function hooks()
    {
        \add_filter('timber/context', [$this, 'addSocialLinks']);
    }

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
