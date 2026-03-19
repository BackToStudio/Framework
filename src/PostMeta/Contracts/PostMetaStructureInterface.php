<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\Contracts;

/**
 * Full configuration contract for a post meta structure.
 *
 * Composes three segregated interfaces (ISP):
 * - {@see MetaKeyAwareInterface} — identity (key, object type)
 * - {@see MetaSchemaInterface} — schema (type, label, defaults)
 * - {@see MetaAccessControlInterface} — access control (sanitization, REST, revisions)
 *
 * Existing consumers that depend on the full interface continue to work unchanged.
 * New consumers can depend on only the sub-interface they need.
 */
interface PostMetaStructureInterface extends MetaKeyAwareInterface, MetaSchemaInterface, MetaAccessControlInterface
{
}
