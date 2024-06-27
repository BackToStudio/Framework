<?php

namespace BackTo\Framework\Options;

use BackTo\Framework\Contracts\Hooks;

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

