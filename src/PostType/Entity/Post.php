<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Entity;

use DateTimeInterface;
use BackTo\Framework\Compose\HasId;
use BackTo\Framework\Compose\HasParentId;
use BackTo\Framework\Compose\HasSlug;
use BackTo\Framework\PostType\Contracts\PostInterface;

final class Post implements PostInterface
{

    use HasId;
    use HasSlug;
    use HasParentId;

    private string $title = '';

    private string $author = '';

    private PostStatus $status = PostStatus::Draft;

    private string $content = '';
    private string $excerpt = '';

    private string $postType = '';

    private ?DateTimeInterface $publishedAt = null;

    private ?DateTimeInterface $modifiedAt = null;

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): PostInterface
    {
        $this->title = $title;
        return $this;
    }

    public function getAuthor(): string
    {
        return $this->author;
    }

    public function setAuthor(string $author): PostInterface
    {
        $this->author = $author;
        return $this;
    }

    public function getStatus(): PostStatus
    {
        return $this->status;
    }

    public function setStatus(PostStatus|string $status): PostInterface
    {
        $this->status = $status instanceof PostStatus
            ? $status
            : (PostStatus::tryFrom($status) ?? PostStatus::Draft);
        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): PostInterface
    {
        $this->content = $content;
        return $this;
    }

    public function getExcerpt(): string
    {
        return $this->excerpt;
    }

    public function setExcerpt(string $excerpt): PostInterface
    {
        $this->excerpt = $excerpt;
        return $this;
    }

    public function getPostType(): string
    {
        return $this->postType;
    }

    public function setPostType(string $postType): PostInterface
    {
        if (trim($postType) === '') {
            throw new \InvalidArgumentException('Post type cannot be empty.');
        }

        $this->postType = $postType;
        return $this;
    }

    public function getPublishedAt(): ?DateTimeInterface
    {
        return $this->publishedAt;
    }

    public function setPublishedAt(DateTimeInterface $publishedAt): PostInterface
    {
        $this->publishedAt = $publishedAt;
        return $this;
    }

    public function getModifiedAt(): ?DateTimeInterface
    {
        return $this->modifiedAt;
    }

    public function setModifiedAt(DateTimeInterface $modifiedAt): PostInterface
    {
        $this->modifiedAt = $modifiedAt;
        return $this;
    }

    // ── Domain Logic ────────────────────────────────────────

    /**
     * Whether this post is visible to front-end visitors.
     */
    public function isPublished(): bool
    {
        return $this->status === PostStatus::Publish;
    }

    /**
     * Whether the post content can be modified (draft-like statuses).
     */
    public function isDraft(): bool
    {
        return $this->status->isEditable();
    }

    /**
     * Whether the post is in the trash.
     */
    public function isTrashed(): bool
    {
        return $this->status === PostStatus::Trash;
    }

    /**
     * Whether the post has been modified after publication.
     */
    public function hasBeenModifiedAfterPublication(): bool
    {
        if ($this->publishedAt === null || $this->modifiedAt === null) {
            return false;
        }

        return $this->modifiedAt > $this->publishedAt;
    }

    /**
     * Whether the post has non-empty content.
     */
    public function hasContent(): bool
    {
        return trim($this->content) !== '';
    }

    /**
     * Whether the post has a non-empty excerpt.
     */
    public function hasExcerpt(): bool
    {
        return trim($this->excerpt) !== '';
    }
}
