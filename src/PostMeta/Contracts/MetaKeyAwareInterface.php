<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\Contracts;

use BackTo\Framework\PostMeta\ValueObject\MetaKey;

/**
 * Core identity contract for a post meta structure.
 *
 * Segregated from PostMetaStructureInterface (ISP):
 * consumers that only need to identify a meta field depend on this,
 * not the full configuration interface.
 */
interface MetaKeyAwareInterface
{
    public function getMetaKey(): MetaKey;

    public function setMetaKey(MetaKey|string $metaKey): MetaKeyAwareInterface;

    public function getObjectType(): string;

    public function setObjectType(string $objectType): MetaKeyAwareInterface;

    public function getObjectSubtype(): string;

    public function setObjectSubtype(string $objectSubtype): MetaKeyAwareInterface;
}
