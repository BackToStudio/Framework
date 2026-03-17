<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Generator;

use BackTo\Framework\Seo\Schema;
use BackTo\Framework\Seo\Schema\SchemaType;

/**
 * Generates a BreadcrumbList schema from the current WordPress page context.
 */
class BreadcrumbSchemaGenerator
{
    public function generate(): ?SchemaType
    {
        $items = $this->buildItems();

        if (\count($items) < 2) {
            return null;
        }

        $listItems = [];

        foreach ($items as $position => $item) {
            $listItem = Schema::listItem()
                ->position($position + 1)
                ->name($item['name']);

            if (isset($item['url'])) {
                $listItem->url($item['url']);
            }

            $listItems[] = $listItem;
        }

        return Schema::breadcrumbList()->items($listItems);
    }

    /**
     * @return array<int, array{name: string, url?: string}>
     */
    private function buildItems(): array
    {
        $items = [];
        $items[] = ['name' => \get_bloginfo('name'), 'url' => \home_url('/')];

        if (\is_singular()) {
            $post = \get_queried_object();

            if (!$post instanceof \WP_Post) {
                return $items;
            }

            if ($post->post_type === 'post') {
                $this->addPostArchive($items);
                $this->addPostCategories($items, $post);
            } elseif ($post->post_type === 'page') {
                $this->addPageAncestors($items, $post);
            } else {
                $this->addCustomPostTypeArchive($items, $post);
            }

            $items[] = ['name' => $post->post_title];
        } elseif (\is_category() || \is_tag() || \is_tax()) {
            $term = \get_queried_object();

            if ($term instanceof \WP_Term) {
                $this->addTermAncestors($items, $term);
                $items[] = ['name' => $term->name];
            }
        } elseif (\is_post_type_archive()) {
            $postType = \get_queried_object();

            if ($postType instanceof \WP_Post_Type) {
                $items[] = ['name' => $postType->labels->name];
            }
        } elseif (\is_author()) {
            $author = \get_queried_object();

            if ($author instanceof \WP_User) {
                $items[] = ['name' => $author->display_name];
            }
        } elseif (\is_search()) {
            $items[] = ['name' => \get_search_query()];
        }

        return $items;
    }

    /**
     * @param array<int, array{name: string, url?: string}> $items
     */
    private function addPostArchive(array &$items): void
    {
        $pageForPosts = (int) \get_option('page_for_posts');

        if ($pageForPosts > 0) {
            $blogPage = \get_post($pageForPosts);

            if ($blogPage instanceof \WP_Post) {
                $items[] = ['name' => $blogPage->post_title, 'url' => \get_permalink($blogPage)];
            }
        }
    }

    /**
     * @param array<int, array{name: string, url?: string}> $items
     */
    private function addPostCategories(array &$items, \WP_Post $post): void
    {
        $categories = \get_the_category($post->ID);

        if (!empty($categories)) {
            $category = $categories[0];
            $link = \get_category_link($category->term_id);

            if (\is_string($link)) {
                $items[] = ['name' => $category->name, 'url' => $link];
            }
        }
    }

    /**
     * @param array<int, array{name: string, url?: string}> $items
     */
    private function addPageAncestors(array &$items, \WP_Post $page): void
    {
        $ancestors = \get_post_ancestors($page);

        foreach (array_reverse($ancestors) as $ancestorId) {
            $ancestor = \get_post($ancestorId);

            if ($ancestor instanceof \WP_Post) {
                $items[] = ['name' => $ancestor->post_title, 'url' => \get_permalink($ancestor)];
            }
        }
    }

    /**
     * @param array<int, array{name: string, url?: string}> $items
     */
    private function addCustomPostTypeArchive(array &$items, \WP_Post $post): void
    {
        $postTypeObj = \get_post_type_object($post->post_type);

        if ($postTypeObj !== null && $postTypeObj->has_archive) {
            $archiveUrl = \get_post_type_archive_link($post->post_type);

            if (\is_string($archiveUrl)) {
                $items[] = ['name' => $postTypeObj->labels->name, 'url' => $archiveUrl];
            }
        }
    }

    /**
     * @param array<int, array{name: string, url?: string}> $items
     */
    private function addTermAncestors(array &$items, \WP_Term $term): void
    {
        if (!$term->parent) {
            return;
        }

        $ancestors = \get_ancestors($term->term_id, $term->taxonomy, 'taxonomy');

        foreach (array_reverse($ancestors) as $ancestorId) {
            $ancestor = \get_term($ancestorId, $term->taxonomy);

            if ($ancestor instanceof \WP_Term) {
                $link = \get_term_link($ancestor);

                if (\is_string($link)) {
                    $items[] = ['name' => $ancestor->name, 'url' => $link];
                }
            }
        }
    }
}
