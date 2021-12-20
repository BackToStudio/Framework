<?php

namespace Fantassin\Core\WordPress\Options;

use Fantassin\Core\WordPress\Contracts\Hooks;

class SocialLinksInTimberContext implements Hooks
{

    /**
     * @var SocialLinksFactory
     */
    protected $factory;

    public function __construct(SocialLinksFactory $socialLinksFactory)
    {
        $this->factory = $socialLinksFactory;
    }

    public function hooks()
    {
        \add_filter('timber/context', [$this, 'addSocialLinksToContext']);
    }

    public function addSocialLinksToContext(array $context): array
    {
        $strategy = $this->factory->createSocialLinkStrategy();

        if (is_null($strategy)) {
            return $context;
        }

        $context['facebook'] = $strategy->getFacebookLink();
        $context['twitter'] = $strategy->getTwitterLink();
        $context['pinterest'] = $strategy->getPinterestLink();
        $context['linkedin'] = $strategy->getLinkedInLink();
        $context['instagram'] = $strategy->getInstagramLink();
        $context['youtube'] = $strategy->getYouTubeLink();

        return $context;
    }
}

