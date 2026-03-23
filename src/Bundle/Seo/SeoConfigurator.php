<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo;

use BackTo\Framework\Contracts\ModuleConfiguratorInterface;

/**
 * Fluent configurator for SEO module parameters.
 *
 * Used in config/seo.php to override defaults without
 * knowing the underlying parameter key names:
 *
 *     return static function (SeoConfigurator $seo): void {
 *         $seo
 *             ->titleSeparator('-')
 *             ->robotsDefault('noindex, nofollow');
 *     };
 */
final class SeoConfigurator implements ModuleConfiguratorInterface
{
    /** @var array<string, mixed> */
    private array $overrides = [];

    public function titleSeparator(string $separator): self
    {
        $this->overrides['seo.title_separator'] = $separator;

        return $this;
    }

    public function robotsDefault(string $robots): self
    {
        $this->overrides['seo.robots_default'] = $robots;

        return $this;
    }

    public function sitemapEnabled(bool $enabled): self
    {
        $this->overrides['seo.sitemap_enabled'] = $enabled;

        return $this;
    }

    public function sitemapUsersEnabled(bool $enabled): self
    {
        $this->overrides['seo.sitemap_users_enabled'] = $enabled;

        return $this;
    }

    /**
     * @param string[] $postTypes Post type keys to exclude (e.g. ['attachment', 'revision'])
     */
    public function sitemapExcludePostTypes(array $postTypes): self
    {
        $this->overrides['seo.sitemap_excluded_post_types'] = $postTypes;

        return $this;
    }

    /**
     * @param string[] $taxonomies Taxonomy slugs to exclude (e.g. ['post_tag'])
     */
    public function sitemapExcludeTaxonomies(array $taxonomies): self
    {
        $this->overrides['seo.sitemap_excluded_taxonomies'] = $taxonomies;

        return $this;
    }

    /**
     * @param int[] $postIds Specific post IDs to exclude
     */
    public function sitemapExcludePostIds(array $postIds): self
    {
        $this->overrides['seo.sitemap_excluded_post_ids'] = $postIds;

        return $this;
    }

    /**
     * @param int[] $termIds Specific term IDs to exclude
     */
    public function sitemapExcludeTermIds(array $termIds): self
    {
        $this->overrides['seo.sitemap_excluded_term_ids'] = $termIds;

        return $this;
    }

    public function sitemapMaxUrls(int $maxUrls): self
    {
        if ($maxUrls < 1 || $maxUrls > 50000) {
            throw new \InvalidArgumentException(\sprintf('Sitemap max URLs must be between 1 and 50000, got %d.', $maxUrls));
        }

        $this->overrides['seo.sitemap_max_urls'] = $maxUrls;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toParameters(): array
    {
        return $this->overrides;
    }
}
