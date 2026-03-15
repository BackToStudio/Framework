<?php

namespace BackTo\Framework\Seo\Provider;

use BackTo\Framework\Seo\Contracts\SeoProviderInterface;

/**
 * SEO provider for Yoast SEO (wordpress-seo).
 */
class YoastProvider implements SeoProviderInterface
{
    private const PLUGIN_FILE = 'wordpress-seo/wp-seo.php';
    private const SOCIAL_OPTION = 'wpseo_social';

    public function getName(): string
    {
        return 'yoast';
    }

    public function isActive(): bool
    {
        return \is_plugin_active(self::PLUGIN_FILE);
    }

    // --- Social Links ---

    public function getFacebookUrl(): ?string
    {
        return $this->getSocialOption('facebook_site');
    }

    public function getTwitterUrl(): ?string
    {
        return $this->getSocialOption('twitter_site');
    }

    public function getInstagramUrl(): ?string
    {
        return $this->getSocialOption('instagram_url');
    }

    public function getLinkedInUrl(): ?string
    {
        return $this->getSocialOption('linkedin_url');
    }

    public function getPinterestUrl(): ?string
    {
        return $this->getSocialOption('pinterest_url');
    }

    public function getYouTubeUrl(): ?string
    {
        return $this->getSocialOption('youtube_url');
    }

    public function getSocialLinks(): array
    {
        return [
            'facebook' => $this->getFacebookUrl(),
            'twitter' => $this->getTwitterUrl(),
            'instagram' => $this->getInstagramUrl(),
            'linkedin' => $this->getLinkedInUrl(),
            'pinterest' => $this->getPinterestUrl(),
            'youtube' => $this->getYouTubeUrl(),
        ];
    }

    // --- Meta ---

    public function getTitle(?int $postId = null): ?string
    {
        return $this->getPostMeta($postId, '_yoast_wpseo_title');
    }

    public function getDescription(?int $postId = null): ?string
    {
        return $this->getPostMeta($postId, '_yoast_wpseo_metadesc');
    }

    public function getCanonicalUrl(?int $postId = null): ?string
    {
        return $this->getPostMeta($postId, '_yoast_wpseo_canonical');
    }

    public function getOgTitle(?int $postId = null): ?string
    {
        return $this->getPostMeta($postId, '_yoast_wpseo_opengraph-title');
    }

    public function getOgDescription(?int $postId = null): ?string
    {
        return $this->getPostMeta($postId, '_yoast_wpseo_opengraph-description');
    }

    public function getOgImageUrl(?int $postId = null): ?string
    {
        return $this->getPostMeta($postId, '_yoast_wpseo_opengraph-image');
    }

    private function getSocialOption(string $key): ?string
    {
        $options = \get_option(self::SOCIAL_OPTION, []);

        if (!is_array($options) || !isset($options[$key])) {
            return null;
        }

        $value = $options[$key];

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function getPostMeta(?int $postId, string $key): ?string
    {
        $postId = $postId ?? \get_the_ID();

        if (!$postId) {
            return null;
        }

        $value = \get_post_meta($postId, $key, true);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
