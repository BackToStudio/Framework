<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Contracts;

use DateTimeInterface;
use BackTo\Framework\Contracts\IdInterface;
use BackTo\Framework\Contracts\ParentIdInterface;
use BackTo\Framework\Contracts\SlugInterface;
use BackTo\Framework\PostMeta\Contracts\PostReferenceInterface;
use BackTo\Framework\PostType\Entity\PostStatus;

interface PostInterface extends IdInterface, SlugInterface, ParentIdInterface, PostReferenceInterface
{


    public function getTitle(): string;


    public function setTitle(string $title): PostInterface;


    public function getContent(): string;


    public function setContent(string $content): PostInterface;


    public function getAuthor(): string;


    public function setAuthor(string $author): PostInterface;


    public function getStatus(): PostStatus;


    public function setStatus(PostStatus|string $status): PostInterface;


    public function getPostType(): string;


    public function setPostType(string $postType): PostInterface;


    public function getPublishedAt(): ?DateTimeInterface;


    public function setPublishedAt(DateTimeInterface $publishedAt): PostInterface;


    public function getModifiedAt(): ?DateTimeInterface;


    public function setModifiedAt(DateTimeInterface $modifiedAt): PostInterface;
}
