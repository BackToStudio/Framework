<?php

namespace BackTo\Framework\PostType\Entity;

use DateTimeInterface;
use BackTo\Framework\Compose\HasId;
use BackTo\Framework\Compose\HasParentId;
use BackTo\Framework\Compose\HasSlug;
use BackTo\Framework\PostType\Contracts\PostInterface;

class Post implements PostInterface
{

    use HasId;
    use HasSlug;
    use HasParentId;

    protected string $title = '';

    protected string $author = '';

    protected string $status = '';

    protected string $content = '';
    protected string $excerpt = '';

    /**
     * @var int
     */
    protected $parentId = null;

    protected string $postType = '';

    protected ?DateTimeInterface $publishedAt = null;

    protected ?DateTimeInterface $modifiedAt = null;

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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): PostInterface
    {
        $this->status = $status;
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
}
