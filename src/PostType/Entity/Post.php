<?php

namespace Fantassin\Core\WordPress\PostType\Entity;

use DateTimeInterface;
use Fantassin\Core\WordPress\Compose\HasId;
use Fantassin\Core\WordPress\Compose\HasParentId;
use Fantassin\Core\WordPress\Compose\HasSlug;
use Fantassin\Core\WordPress\PostType\Contracts\PostInterface;

class Post implements PostInterface
{

    use HasId;
    use HasSlug;
    use HasParentId;

    /**
     * @var string
     */
    protected $title = '';

    /**
     * @var string
     */
    protected $author = '';

    /**
     * @var string
     */
    protected $status = '';

    /**
     * @var string
     */
    protected $content = '';

    /**
     * @var int
     */
    protected $parentId = null;

    /**
     * @var string
     */
    protected $postType = '';

    /**
     * @var DateTimeInterface
     */
    protected $publishedAt = null;

    /**
     * @var DateTimeInterface
     */
    protected $modifiedAt = null;

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
