<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Factory;

use BackTo\Framework\PostType\Contracts\PostInterface;
use BackTo\Framework\PostType\Entity\Post;
use BackTo\Framework\PostType\Entity\PostStatus;
use BackTo\Framework\Contracts\LoggerInterface;
use DateTimeImmutable;
use Exception;
use WP_Post;

final class PostFactory
{
    private readonly LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    public function create(WP_Post $wpPost): PostInterface
    {
        $post = new Post();
        $post->setId($wpPost->ID);
        $post->setTitle($wpPost->post_title);
        $post->setContent($wpPost->post_content);
        $post->setStatus(PostStatus::tryFrom($wpPost->post_status) ?? PostStatus::Draft);
        $post->setSlug($wpPost->post_name);
        $post->setExcerpt($wpPost->post_excerpt);
        $post->setParentId($wpPost->post_parent);
        $post->setPostType($wpPost->post_type);
        $post->setAuthor($wpPost->post_author);

        try {
            $modifiedAt = new DateTimeImmutable($wpPost->post_modified);
            $post->setModifiedAt($modifiedAt);

            $publishedAt = new DateTimeImmutable($wpPost->post_date);
            $post->setPublishedAt($publishedAt);
        } catch (Exception $e) {
            $this->logger->warning(\sprintf('Invalid post dates for post %d: %s', $wpPost->ID, $e->getMessage()));
        }

        return $post;
    }


    public function createFromPosts(array $wpPosts): array
    {
        return array_map(
            function (WP_Post $wpPost) {
                return $this->create($wpPost);
            },
            $wpPosts
        );
    }

}
