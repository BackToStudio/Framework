<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema\Generator;

use BackTo\Framework\Contracts\ContentQueryInterface;
use BackTo\Framework\Contracts\SiteContextInterface;
use BackTo\Framework\Seo\Schema;
use BackTo\Framework\Seo\Schema\SchemaType;

/**
 * Generates an Article schema from a WordPress post.
 *
 * Links to "#organization" as publisher and "#website" as isPartOf.
 */
final class ArticleSchemaGenerator
{
    private readonly ContentQueryInterface $contentQuery;
    private readonly SiteContextInterface $siteContext;

    public function __construct(ContentQueryInterface $contentQuery, SiteContextInterface $siteContext)
    {
        $this->contentQuery = $contentQuery;
        $this->siteContext = $siteContext;
    }

    public function generate(?int $postId = null): ?SchemaType
    {
        $post = $this->contentQuery->getPost($postId);

        if ($post === null) {
            return null;
        }

        if ($post->post_type !== 'post') {
            return null;
        }

        $siteUrl = $this->siteContext->getHomeUrl() . '/';
        $permalink = $this->contentQuery->getPermalink($post->ID);

        $article = Schema::article()
            ->id($permalink . '#article')
            ->headline($post->post_title)
            ->url($permalink)
            ->datePublished($this->contentQuery->getTheDate('c', $post))
            ->dateModified($this->contentQuery->getTheModifiedDate('c', $post))
            ->set('isPartOf', Schema::ref($siteUrl . '#website'))
            ->set('publisher', Schema::ref($siteUrl . '#organization'));

        $author = $this->contentQuery->getUserdata($post->post_author);

        if ($author !== false) {
            $article->author(Schema::person()->name($author->display_name));
        }

        $thumbnailUrl = $this->contentQuery->getThePostThumbnailUrl($post, 'full');

        if (is_string($thumbnailUrl) && $thumbnailUrl !== '') {
            $article->image($thumbnailUrl);
        }

        $description = $this->contentQuery->getTheExcerpt($post);

        if ($description !== '') {
            $article->description($description);
        }

        return $article;
    }
}
