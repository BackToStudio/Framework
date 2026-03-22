<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Hooks;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;

/**
 * Configures WordPress native sitemaps (introduced in WP 5.5).
 *
 * Provides fine-grained control over which post types, taxonomies,
 * and users are included in wp-sitemap.xml, plus the ability to
 * disable sitemaps entirely.
 *
 * WordPress sitemap filters used:
 * - wp_sitemaps_enabled             → enable/disable sitemaps globally
 * - wp_sitemaps_post_types          → filter included post types
 * - wp_sitemaps_taxonomies          → filter included taxonomies
 * - wp_sitemaps_add_provider        → remove user sitemap provider
 * - wp_sitemaps_max_urls            → control max URLs per sitemap page
 * - wp_sitemaps_posts_query_args    → exclude specific post IDs
 * - wp_sitemaps_taxonomies_query_args → exclude specific term IDs
 */
final class ConfigureSitemaps implements Hooks
{
    private readonly HookDispatcherInterface $hookDispatcher;

    private bool $enabled = true;
    private bool $usersEnabled = true;

    /** @var string[] Post type keys to exclude from sitemap */
    private array $excludedPostTypes = [];

    /** @var string[] Taxonomy slugs to exclude from sitemap */
    private array $excludedTaxonomies = [];

    /** @var int[] Post IDs to exclude from sitemap */
    private array $excludedPostIds = [];

    /** @var int[] Term IDs to exclude from sitemap */
    private array $excludedTermIds = [];

    private int $maxUrls = 2000;

    public function __construct(HookDispatcherInterface $hookDispatcher)
    {
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        if (!$this->enabled) {
            $this->hookDispatcher->addFilter('wp_sitemaps_enabled', '__return_false');

            return;
        }

        if ($this->excludedPostTypes !== []) {
            $this->hookDispatcher->addFilter('wp_sitemaps_post_types', [$this, 'filterPostTypes']);
        }

        if ($this->excludedTaxonomies !== []) {
            $this->hookDispatcher->addFilter('wp_sitemaps_taxonomies', [$this, 'filterTaxonomies']);
        }

        if (!$this->usersEnabled) {
            $this->hookDispatcher->addFilter('wp_sitemaps_add_provider', [$this, 'removeUsersProvider'], 10, 2);
        }

        if ($this->maxUrls !== 2000) {
            $this->hookDispatcher->addFilter('wp_sitemaps_max_urls', [$this, 'filterMaxUrls']);
        }

        if ($this->excludedPostIds !== []) {
            $this->hookDispatcher->addFilter('wp_sitemaps_posts_query_args', [$this, 'excludePostIds']);
        }

        if ($this->excludedTermIds !== []) {
            $this->hookDispatcher->addFilter('wp_sitemaps_taxonomies_query_args', [$this, 'excludeTermIds']);
        }
    }

    // -- Setters (called by compiler pass from config parameters) --

    public function setEnabled(bool $enabled): self
    {
        $this->enabled = $enabled;

        return $this;
    }

    public function setUsersEnabled(bool $enabled): self
    {
        $this->usersEnabled = $enabled;

        return $this;
    }

    /**
     * @param string[] $postTypes
     */
    public function setExcludedPostTypes(array $postTypes): self
    {
        $this->excludedPostTypes = $postTypes;

        return $this;
    }

    /**
     * @param string[] $taxonomies
     */
    public function setExcludedTaxonomies(array $taxonomies): self
    {
        $this->excludedTaxonomies = $taxonomies;

        return $this;
    }

    /**
     * @param int[] $postIds
     */
    public function setExcludedPostIds(array $postIds): self
    {
        $this->excludedPostIds = $postIds;

        return $this;
    }

    /**
     * @param int[] $termIds
     */
    public function setExcludedTermIds(array $termIds): self
    {
        $this->excludedTermIds = $termIds;

        return $this;
    }

    public function setMaxUrls(int $maxUrls): self
    {
        $this->maxUrls = $maxUrls;

        return $this;
    }

    // -- Filter callbacks --

    /**
     * @param array<string, \WP_Post_Type> $postTypes
     * @return array<string, \WP_Post_Type>
     */
    public function filterPostTypes(array $postTypes): array
    {
        foreach ($this->excludedPostTypes as $type) {
            unset($postTypes[$type]);
        }

        return $postTypes;
    }

    /**
     * @param array<string, \WP_Taxonomy> $taxonomies
     * @return array<string, \WP_Taxonomy>
     */
    public function filterTaxonomies(array $taxonomies): array
    {
        foreach ($this->excludedTaxonomies as $taxonomy) {
            unset($taxonomies[$taxonomy]);
        }

        return $taxonomies;
    }

    /**
     * @param \WP_Sitemaps_Provider|null $provider
     */
    public function removeUsersProvider(mixed $provider, string $name): mixed
    {
        if ($name === 'users') {
            return null;
        }

        return $provider;
    }

    public function filterMaxUrls(int $maxUrls): int
    {
        return $this->maxUrls;
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function excludePostIds(array $args): array
    {
        $existing = isset($args['post__not_in']) && \is_array($args['post__not_in'])
            ? $args['post__not_in']
            : [];

        $args['post__not_in'] = \array_merge($existing, $this->excludedPostIds);

        return $args;
    }

    /**
     * @param array<string, mixed> $args
     * @return array<string, mixed>
     */
    public function excludeTermIds(array $args): array
    {
        $existing = isset($args['exclude']) && \is_array($args['exclude'])
            ? $args['exclude']
            : [];

        $args['exclude'] = \array_merge($existing, $this->excludedTermIds);

        return $args;
    }

    // -- Getters (for testing) --

    public function isEnabled(): bool
    {
        return $this->enabled;
    }

    public function isUsersEnabled(): bool
    {
        return $this->usersEnabled;
    }

    /** @return string[] */
    public function getExcludedPostTypes(): array
    {
        return $this->excludedPostTypes;
    }

    /** @return string[] */
    public function getExcludedTaxonomies(): array
    {
        return $this->excludedTaxonomies;
    }

    /** @return int[] */
    public function getExcludedPostIds(): array
    {
        return $this->excludedPostIds;
    }

    /** @return int[] */
    public function getExcludedTermIds(): array
    {
        return $this->excludedTermIds;
    }

    public function getMaxUrls(): int
    {
        return $this->maxUrls;
    }
}
