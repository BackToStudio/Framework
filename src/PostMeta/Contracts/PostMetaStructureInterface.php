<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\Contracts;

use BackTo\Framework\PostMeta\ValueObject\MetaKey;

interface PostMetaStructureInterface
{
    public function getType(): string;

    public function setType(string $type): PostMetaStructureInterface;

    public function getObjectType(): string;

    public function setObjectType(string $objectType): PostMetaStructureInterface;

    public function getMetaKey(): MetaKey;

    public function setMetaKey(MetaKey|string $metaKey): PostMetaStructureInterface;

    /**
     * @return array<string, mixed>
     */
    public function getArgs(): array;

    /**
     * @param array<string, mixed> $args
     */
    public function setArgs(array $args): PostMetaStructureInterface;

    public function getObjectSubtype(): string;

    public function setObjectSubtype(string $objectSubtype): PostMetaStructureInterface;

    public function getLabel(): string;

    public function setLabel(string $label): PostMetaStructureInterface;

    public function getDescription(): string;

    public function setDescription(string $description): PostMetaStructureInterface;

    public function isSingle(): bool;

    public function setSingle(bool $single): PostMetaStructureInterface;

    public function getDefault(): mixed;

    public function setDefault(mixed $default): PostMetaStructureInterface;

    public function getSanitizeCallback(): ?callable;

    public function setSanitizeCallback(callable $callback): PostMetaStructureInterface;

    public function getAuthCallback(): ?callable;

    public function setAuthCallback(?callable $callback): PostMetaStructureInterface;

    public function isShowInRest(): bool;

    public function dontShowInRest(): PostMetaStructureInterface;

    public function showInRest(): PostMetaStructureInterface;

    public function setShowInRest(bool $showInRest): PostMetaStructureInterface;

    public function isRevisionsEnabled(): bool;

    public function setRevisionsEnabled(bool $enabled): PostMetaStructureInterface;
}
