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
    public function __construct(array $options){
        $this->setOptions($options);
    }

    /**
     * @return string|null
     */
    public function getFacebookLink(): ?string
    {
        return $this->getKey('facebook_site');
    }

    /**
     * @return string|null
     */
    public function getTwitterLink(): ?string
    {
        return $this->getKey('twitter_site');
    }

    /**
     * @return string|null
     */
    public function getPinterestLink(): ?string
    {
        return $this->getKey('twitter_site');
    }

    /**
     * @return string|null
     */
    public function getInstagramLink(): ?string
    {
        return $this->getKey('instagram_url');
    }

    /**
     * @return string|null
     */
    public function getLinkedInLink(): ?string
    {
        return $this->getKey('linkedin_url');
    }

    /**
     * @return string|null
     */
    public function getYouTubeLink(): ?string
    {
        return $this->getKey('youtube_url');
    }
}

