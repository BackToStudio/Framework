<?php

declare(strict_types=1);

namespace BackTo\Framework\Contracts;

/**
 * Abstraction over WordPress content query functions.
 *
 * Replaces direct calls to get_post(), get_posts(), get_permalink(),
 * get_categories(), get_tags(), get_comment(), etc.
 */
interface ContentQueryInterface
{
    public function getPost(?int $postId = null): ?object;

    /**
     * @param array<string, mixed> $args
     * @return object[]
     */
    public function getPosts(array $args = []): array;

    public function getPermalink(int $postId): string|false;

    public function getPostType(int $postId): string|false;

    public function getComment(int $commentId): ?object;

    public function getTheDate(string $format, ?object $post = null): string;

    public function getTheModifiedDate(string $format, ?object $post = null): string;

    public function getUserdata(int $userId): object|false;

    public function getThePostThumbnailUrl(object $post, string $size = 'thumbnail'): string|false;

    public function getTheExcerpt(?object $post = null): string;

    public function getPostTypeArchiveLink(string $postType): string|false;

    /**
     * @return string[]
     */
    public function getObjectTaxonomies(string $postType): array;

    /**
     * @param string|string[] $taxonomy
     * @return object[]|false
     */
    public function getPostTerms(int $postId, string|array $taxonomy): array|false;

    public function getTermLink(object $term): string|false;

    /**
     * @param array<string, mixed> $args
     * @return object[]
     */
    public function getCategories(array $args = []): array;

    /**
     * @param array<string, mixed> $args
     * @return object[]
     */
    public function getTags(array $args = []): array;

    public function getCategoryLink(int $categoryId): string;

    public function getTagLink(int $tagId): string;

    public function getAuthorPostsUrl(int $authorId): string;

    public function getYearLink(int $year): string;

    public function getMonthLink(int $year, int $month): string;

    public function getCurrentPostId(): int|false;

    public function isPostRevision(int $postId): bool;

    public function isPostAutosave(int $postId): bool;

    public function getPostMeta(int $postId, string $key, bool $single = true): mixed;
}
