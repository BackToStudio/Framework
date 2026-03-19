<?php

declare(strict_types=1);

namespace BackTo\Framework\PostType\Entity;

enum PostStatus: string
{
    case Publish = 'publish';
    case Draft = 'draft';
    case Pending = 'pending';
    case Private = 'private';
    case Trash = 'trash';
    case AutoDraft = 'auto-draft';
    case Inherit = 'inherit';
    case Future = 'future';

    public function isPublic(): bool
    {
        return $this === self::Publish;
    }

    public function isEditable(): bool
    {
        return in_array($this, [self::Draft, self::Pending, self::AutoDraft, self::Future], true);
    }

    public function isViewable(): bool
    {
        return in_array($this, [self::Publish, self::Private], true);
    }

    /**
     * Whether the status represents a trashed post.
     */
    public function isTrashed(): bool
    {
        return $this === self::Trash;
    }

    /**
     * Whether the status represents a scheduled (future) post.
     */
    public function isScheduled(): bool
    {
        return $this === self::Future;
    }
}
