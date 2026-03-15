<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Provider;

use BackTo\Framework\Seo\Contracts\SeoProviderInterface;

/**
 * SEO provider for SEOPress.
 */
class SeoPressProvider implements SeoProviderInterface
{
    private const PLUGIN_FILE = 'wp-seopress/seopress.php';
    private const SOCIAL_OPTION = 'seopress_social_option_name';

    public function getName(): string
    {
        return 'seopress';
    }

    public function isActive(): bool
    {
        return \is_plugin_active(self::PLUGIN_FILE);
    }

    // --- Social Links ---

    public function getFacebookUrl(): ?string
    {
        return $this->getSocialOption('seopress_social_accounts_facebook');
    }

    public function getTwitterUrl(): ?string
    {
        return $this->getSocialOption('seopress_social_accounts_twitter');
    }

    public function getInstagramUrl(): ?string
    {
        return $this->getSocialOption('seopress_social_accounts_instagram');
    }

    public function getLinkedInUrl(): ?string
    {
        return $this->getSocialOption('seopress_social_accounts_linkedin');
    }

    public function getPinterestUrl(): ?string
    {
        return $this->getSocialOption('seopress_social_accounts_pinterest');
    }

    public function getYouTubeUrl(): ?string
    {
        return $this->getSocialOption('seopress_social_accounts_youtube');
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
        return $this->getPostMeta($postId, '_seopress_titles_title');
    }

    public function getDescription(?int $postId = null): ?string
    {
        return $this->getPostMeta($postId, '_seopress_titles_desc');
    }

    public function getCanonicalUrl(?int $postId = null): ?string
    {
        return $this->getPostMeta($postId, '_seopress_robots_canonical');
    }

    public function getOgTitle(?int $postId = null): ?string
    {
        return $this->getPostMeta($postId, '_seopress_social_fb_title');
    }

    public function getOgDescription(?int $postId = null): ?string
    {
        return $this->getPostMeta($postId, '_seopress_social_fb_desc');
    }

    public function getOgImageUrl(?int $postId = null): ?string
    {
        return $this->getPostMeta($postId, '_seopress_social_fb_img');
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
