<?php

namespace Fantassin\Core\WordPress\Options;

use Fantassin\Core\WordPress\Contracts\Hooks;

interface SocialLinksStrategyInterface
{

    /**
     * @return string|null
     */
    public function getTwitterLink(): ?string;

    /**
     * @return string|null
     */
    public function getPinterestLink(): ?string;

    /**
     * @return string|null
     */
    public function getInstagramLink(): ?string;

    /**
     * @return string|null
     */
    public function getLinkedInLink(): ?string;

    /**
     * @return string|null
     */
    public function getYouTubeLink(): ?string;
}

