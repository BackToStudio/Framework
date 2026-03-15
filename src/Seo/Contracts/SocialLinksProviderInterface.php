<?php

namespace BackTo\Framework\Seo\Contracts;

/**
 * Provides social media links configured in an SEO plugin.
 */
interface SocialLinksProviderInterface
{
    public function getFacebookUrl(): ?string;

    public function getTwitterUrl(): ?string;

    public function getInstagramUrl(): ?string;

    public function getLinkedInUrl(): ?string;

    public function getPinterestUrl(): ?string;

    public function getYouTubeUrl(): ?string;

    /**
     * Return all social links as an associative array.
     *
     * @return array<string, string|null>
     */
    public function getSocialLinks(): array;
}
