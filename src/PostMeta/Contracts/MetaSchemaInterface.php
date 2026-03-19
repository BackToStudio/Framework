<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\Contracts;

/**
 * Schema definition contract for a post meta field.
 *
 * Segregated from PostMetaStructureInterface (ISP):
 * consumers that configure meta field types, labels, and defaults
 * depend on this interface.
 */
interface MetaSchemaInterface
{
    public function getType(): string;

    public function setType(string $type): MetaSchemaInterface;

    public function getLabel(): string;

    public function setLabel(string $label): MetaSchemaInterface;

    public function getDescription(): string;

    public function setDescription(string $description): MetaSchemaInterface;

    public function isSingle(): bool;

    public function setSingle(bool $single): MetaSchemaInterface;

    public function getDefault(): mixed;

    public function setDefault(mixed $default): MetaSchemaInterface;

    /**
     * @return array<string, mixed>
     */
    public function getArgs(): array;

    /**
     * @param array<string, mixed> $args
     */
    public function setArgs(array $args): MetaSchemaInterface;
}
