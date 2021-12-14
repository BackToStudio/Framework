<?php

namespace Fantassin\Core\WordPress\Options\ThirdParty;

use Fantassin\Core\WordPress\Options\HasArrayOptions;
use Fantassin\Core\WordPress\Options\SocialLinksStrategyInterface;

class YoastOptionsStrategy implements SocialLinksStrategyInterface
{

    use HasArrayOptions;

    /**
     * @param array $options
     */
    public function __construct(array $options)
    {
        $this->setOptions($options);
    }

    /**
     * @return string|null
     */
    public function getFacebookLink(): ?string
    {
        return $this->getString('facebook_site');
    }

    /**
     * @return string|null
     */
    public function getTwitterLink(): ?string
    {
        return $this->getString('twitter_site');
    }

    /**
     * @return string|null
     */
    public function getPinterestLink(): ?string
    {
        return $this->getString('pinterest_url');
    }

    /**
     * @return string|null
     */
    public function getInstagramLink(): ?string
    {
        return $this->getString('instagram_url');
    }

    /**
     * @return string|null
     */
    public function getLinkedInLink(): ?string
    {
        return $this->getString('linkedin_url');
    }

    /**
     * @return string|null
     */
    public function getYouTubeLink(): ?string
    {
        return $this->getString('youtube_url');
    }
}

