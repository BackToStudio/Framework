<?php

namespace Fantassin\Core\WordPress\Options\ThirdParty;

use Fantassin\Core\WordPress\Options\HasArrayOptions;
use Fantassin\Core\WordPress\Options\SocialLinksStrategyInterface;

class SeoPressOptionsStrategy implements SocialLinksStrategyInterface
{
    use HasArrayOptions;

    /**
     * @param array $options
     */
    public function __construct(array $options){
        $this->setOptions( $options );
    }

    /**
     * @return string|null
     */
    public function getFacebookLink(): ?string
    {
        return $this->getKey('seopress_social_accounts_facebook');
    }

    /**
     * @return string|null
     */
    public function getTwitterLink(): ?string
    {
        return $this->getKey('seopress_social_accounts_twitter');
    }

    /**
     * @return string|null
     */
    public function getPinterestLink(): ?string
    {
        return $this->getKey('seopress_social_accounts_pinterest');
    }

    /**
     * @return string|null
     */
    public function getInstagramLink(): ?string
    {
        return null;
    }

    /**
     * @return string|null
     */
    public function getLinkedInLink(): ?string
    {
        return $this->getKey('seopress_social_accounts_linkedin');
    }

    /**
     * @return string|null
     */
    public function getYouTubeLink(): ?string
    {
        return null;
    }
}

