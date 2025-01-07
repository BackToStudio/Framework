<?php

namespace BackTo\Framework\PostMeta\Contracts;

use BackTo\Framework\PostMeta\Contracts\PostMetaInterface;

interface PostMetaStructureInterface
{
    public function getObjectType(): string;
    
    public function setObjectType(string $objectType): PostMetaStructureInterface;

    public function getMetaKey(): string;

    public function setMetaKey(string $label): PostMetaStructureInterface;

    public function getArgs(): array;

    public function setArgs(array $args): PostMetaStructureInterface;

    public function getObjectSubtype(): ?string;

    public function setObjectSubtype(string $objectSubtype): PostMetaStructureInterface;

    public function getLabel(): string;

    public function setLabel(string $label): PostMetaStructureInterface;

    public function getDescription(): ?string;

    public function setDescription(?string $description): PostMetaStructureInterface;

    public function isSingle(): bool;

    public function setSingle(bool $single): PostMetaStructureInterface;

    public function getDefault();

    public function setDefault($default): PostMetaStructureInterface;

    public function getSanitizeCallback(): ?callable;

    public function setSanitizeCallback(callable $callback): PostMetaStructureInterface;

    public function getAuthCallback(): ?callable;

    public function setAuthCallback(callable $callback): PostMetaStructureInterface;

    public function isShowInRest();

    public function dontShowInRest(): PostMetaStructureInterface;
    public function showInRest(): PostMetaStructureInterface;

    function setShowInRest(bool $showInRest): PostMetaStructureInterface;

    public function isRevisionsEnabled(): bool;

    public function setRevisionsEnabled(bool $enabled): PostMetaStructureInterface;
}
