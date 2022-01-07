<?php

namespace Fantassin\Core\WordPress\PostType\Contracts;

use DateTimeInterface;

interface PostInterface
{

    /**
     * @return int
     */
    public function getId(): ?int;

    /**
     * @param int $id
     * @return PostInterface
     */
    public function setId(int $id): PostInterface;

    /**
     * @return string
     */
    public function getTitle(): string;

    /**
     * @param string $title
     * @return PostInterface
     */
    public function setTitle(string $title): PostInterface;

    /**
     * @return string
     */
    public function getContent(): string;

    /**
     * @param string $content
     * @return PostInterface
     */
    public function setContent(string $content): PostInterface;

    /**
     * @return string
     */
    public function getAuthor(): string;

    /**
     * @param string $author
     * @return PostInterface
     */
    public function setAuthor(string $author): PostInterface;

    /**
     * @return string
     */
    public function getStatus(): string;

    /**
     * @param string $status
     * @return PostInterface
     */
    public function setStatus(string $status): PostInterface;

    /**
     * @return string
     */
    public function getSlug(): string;

    /**
     * @param string $slug
     * @return PostInterface
     */
    public function setSlug(string $slug): PostInterface;

    /**
     * @return int
     */
    public function getParentId(): ?int;

    /**
     * @param int $parentId
     * @return PostInterface
     */
    public function setParentId(int $parentId): PostInterface;

    /**
     * @return string
     */
    public function getPostType(): string;

    /**
     * @param string $postType
     * @return PostInterface
     */
    public function setPostType(string $postType): PostInterface;

    /**
     * @return DateTimeInterface
     */
    public function getPublishedAt(): ?DateTimeInterface;

    /**
     * @param DateTimeInterface $publishedAt
     * @return PostInterface
     */
    public function setPublishedAt(DateTimeInterface $publishedAt): PostInterface;

    /**
     * @return DateTimeInterface
     */
    public function getModifiedAt(): ?DateTimeInterface;

    /**
     * @param DateTimeInterface $modifiedAt
     * @return PostInterface
     */
    public function setModifiedAt(DateTimeInterface $modifiedAt): PostInterface;
}
