<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Tests;

use BackTo\Framework\PostType\Entity\PostStatus;
use PHPUnit\Framework\TestCase;

class PostStatusDomainLogicTest extends TestCase
{
    public function testIsTrashed(): void
    {
        $this->assertTrue(PostStatus::Trash->isTrashed());
        $this->assertFalse(PostStatus::Publish->isTrashed());
        $this->assertFalse(PostStatus::Draft->isTrashed());
    }

    public function testIsScheduled(): void
    {
        $this->assertTrue(PostStatus::Future->isScheduled());
        $this->assertFalse(PostStatus::Publish->isScheduled());
        $this->assertFalse(PostStatus::Draft->isScheduled());
    }

    /**
     * Verify that status classification is exhaustive:
     * every status should belong to at least one category.
     */
    public function testEveryStatusHasAClassification(): void
    {
        foreach (PostStatus::cases() as $status) {
            $classified = $status->isPublic()
                || $status->isEditable()
                || $status->isViewable()
                || $status->isTrashed()
                || $status === PostStatus::Inherit;

            $this->assertTrue(
                $classified,
                sprintf('PostStatus::%s has no classification', $status->name)
            );
        }
    }
}
