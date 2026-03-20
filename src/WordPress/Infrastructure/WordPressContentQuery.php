<?php

declare(strict_types=1);

namespace BackTo\Framework\WordPress\Infrastructure;

use BackTo\Framework\Contracts\ContentQueryInterface;

final class WordPressContentQuery implements ContentQueryInterface
{
    public function getPost(?int $postId = null): ?object
    {
        $post = \get_post($postId);

        return $post instanceof \WP_Post ? $post : null;
    }

    public function getPosts(array $args = []): array
    {
        return \get_posts($args);
    }

    public function getPermalink(int $postId): string|false
    {
        return \get_permalink($postId);
    }

    public function getPostType(int $postId): string|false
    {
        return \get_post_type($postId);
    }

    public function getComment(int $commentId): ?object
    {
        $comment = \get_comment($commentId);

        return $comment instanceof \WP_Comment ? $comment : null;
    }

    public function getTheDate(string $format, ?object $post = null): string
    {
        return (string) \get_the_date($format, $post);
    }

    public function getTheModifiedDate(string $format, ?object $post = null): string
    {
        return (string) \get_the_modified_date($format, $post);
    }

    public function getUserdata(int $userId): object|false
    {
        return \get_userdata($userId);
    }

    public function getThePostThumbnailUrl(object $post, string $size = 'thumbnail'): string|false
    {
        return \get_the_post_thumbnail_url($post, $size);
    }

    public function getTheExcerpt(?object $post = null): string
    {
        return (string) \get_the_excerpt($post);
    }

    public function getPostTypeArchiveLink(string $postType): string|false
    {
        return \get_post_type_archive_link($postType);
    }

    public function getObjectTaxonomies(string $postType): array
    {
        return \get_object_taxonomies($postType, 'names');
    }

    public function getPostTerms(int $postId, string|array $taxonomy): array|false
    {
        $terms = \wp_get_post_terms($postId, $taxonomy);

        return is_array($terms) ? $terms : false;
    }

    public function getTermLink(object $term): string|false
    {
        $link = \get_term_link($term);

        return is_string($link) ? $link : false;
    }

    public function getCategories(array $args = []): array
    {
        $categories = \get_categories($args);

        return is_array($categories) ? $categories : [];
    }

    public function getTags(array $args = []): array
    {
        $tags = \get_tags($args);

        return is_array($tags) ? $tags : [];
    }

    public function getCategoryLink(int $categoryId): string
    {
        return (string) \get_category_link($categoryId);
    }

    public function getTagLink(int $tagId): string
    {
        return (string) \get_tag_link($tagId);
    }

    public function getAuthorPostsUrl(int $authorId): string
    {
        return (string) \get_author_posts_url($authorId);
    }

    public function getYearLink(int $year): string
    {
        return (string) \get_year_link($year);
    }

    public function getMonthLink(int $year, int $month): string
    {
        return (string) \get_month_link($year, $month);
    }

    public function getCurrentPostId(): int|false
    {
        return \get_the_ID();
    }

    public function isPostRevision(int $postId): bool
    {
        return (bool) \wp_is_post_revision($postId);
    }

    public function isPostAutosave(int $postId): bool
    {
        return (bool) \wp_is_post_autosave($postId);
    }

    public function getPostMeta(int $postId, string $key, bool $single = true): mixed
    {
        return \get_post_meta($postId, $key, $single);
    }
}
