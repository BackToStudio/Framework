<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Generator;

use BackTo\Framework\Seo\Schema;
use BackTo\Framework\Seo\Schema\SchemaType;

/**
 * Generates an Article schema from a WordPress post.
 */
class ArticleSchemaGenerator
{
    public function generate(?int $postId = null): ?SchemaType
    {
        $post = \get_post($postId);

        if (!$post instanceof \WP_Post) {
            return null;
        }

        if ($post->post_type !== 'post') {
            return null;
        }

        $article = Schema::article()
            ->headline($post->post_title)
            ->url(\get_permalink($post))
            ->datePublished(\get_the_date('c', $post))
            ->dateModified(\get_the_modified_date('c', $post));

        $author = \get_userdata($post->post_author);

        if ($author !== false) {
            $article->author(Schema::person()->name($author->display_name));
        }

        $thumbnailUrl = \get_the_post_thumbnail_url($post, 'full');

        if (\is_string($thumbnailUrl) && $thumbnailUrl !== '') {
            $article->image($thumbnailUrl);
        }

        $description = \get_the_excerpt($post);

        if (\is_string($description) && $description !== '') {
            $article->description($description);
        }

        return $article;
    }
}
