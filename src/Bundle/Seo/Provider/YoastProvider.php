<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Provider;

use BackTo\Framework\Contracts\ContentQueryInterface;
use BackTo\Framework\Contracts\PluginCheckerInterface;
use BackTo\Framework\Options\Contracts\OptionsRepositoryInterface;
use BackTo\Framework\Bundle\Seo\Contracts\SeoProviderInterface;

/**
 * SEO provider for Yoast SEO (wordpress-seo).
 */
final class YoastProvider implements SeoProviderInterface
{
    private const PLUGIN_FILE = 'wordpress-seo/wp-seo.php';
    private const SOCIAL_OPTION = 'wpseo_social';

    /** @var array<string, mixed>|null Cached social options to avoid repeated calls */
    private ?array $socialOptionsCache = null;

    public function __construct(
        private readonly PluginCheckerInterface $pluginChecker,
        private readonly OptionsRepositoryInterface $options,
        private readonly ContentQueryInterface $contentQuery,
    ) {}

    public function getName(): string
    {
        return 'yoast';
    }

    public function isActive(): bool
    {
        return $this->pluginChecker->isActive(self::PLUGIN_FILE);
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
        if ($this->socialOptionsCache === null) {
            $options = $this->options->get(self::SOCIAL_OPTION, []);
            $this->socialOptionsCache = is_array($options) ? $options : [];
        }

        if (!isset($this->socialOptionsCache[$key])) {
            return null;
        }

        $value = $this->socialOptionsCache[$key];

        return is_string($value) && $value !== '' ? $value : null;
    }

    private function getPostMeta(?int $postId, string $key): ?string
    {
        $postId = $postId ?? $this->contentQuery->getCurrentPostId();

        if (!$postId) {
            return null;
        }

        $value = $this->contentQuery->getPostMeta($postId, $key, true);

        return is_string($value) && $value !== '' ? $value : null;
    }
}
