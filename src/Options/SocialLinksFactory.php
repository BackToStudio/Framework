<?php

namespace Fantassin\Core\WordPress\Options;

use Fantassin\Core\WordPress\Options\ThirdParty\SeoPressOptionsStrategy;
use Fantassin\Core\WordPress\Options\ThirdParty\YoastOptionsStrategy;

class SocialLinksFactory
{
    use HasArrayOptions;

    /**
     * @var OptionsRepository
     */
    protected $repository;

    public function __construct(OptionsRepository $repository)
    {
        $this->repository = $repository;
        $this->setOptions($this->repository->find('active_plugins'));
    }

    /**
     * @param string $pluginName
     * @return bool
     */
    public function isPluginActive(string $pluginName): bool
    {
        return $this->isInOptions($pluginName);
    }

    public function createSocialLinkStrategy(): ?SocialLinksStrategyInterface
    {
        $strategy = null;

        if ($this->isPluginActive('wp-seopress/seopress.php')) {
            $strategy = new SeoPressOptionsStrategy($this->repository->find('seopress_social_option_name'));
        }

        if ($this->isPluginActive('wordpress-seo/wp-seo.php')) {
            $strategy = new YoastOptionsStrategy($this->repository->find('wpseo_social'));
        }

        return $strategy;
    }
}

