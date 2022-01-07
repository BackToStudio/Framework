<?php

namespace Fantassin\Core\WordPress\PostType\Factory;

use Fantassin\Core\WordPress\PostType\Contracts\PostInterface;
use Fantassin\Core\WordPress\PostType\Entity\Post;
use DateTimeImmutable;
use Exception;
use WP_Post;

class PostFactory
{

    public function create(WP_Post $wpPost): PostInterface
    {
        $post = (new Post())
            ->setId($wpPost->ID)
            ->setTitle($wpPost->post_title)
            ->setContent($wpPost->post_content)
            ->setStatus($wpPost->post_status)
            ->setSlug($wpPost->post_name)
            ->setParentId($wpPost->post_parent)
            ->setPostType($wpPost->post_type)
            ->setAuthor($wpPost->post_author);

        try {
            $modifiedAt = new DateTimeImmutable($wpPost->post_modified);
            $post->setModifiedAt($modifiedAt);

            $publishedAt = new DateTimeImmutable($wpPost->post_date);
            $post->setPublishedAt($publishedAt);
        } catch (Exception $e) {
        }

        return $post;
    }

    /**
     * @param WP_Post[] $wpPosts
     * @return PostInterface[]
     */
    public function createFromPosts(array $wpPosts): array
    {
        return array_map(function (WP_Post $wpPost) {
            return $this->create($wpPost);
        });
    }

}
