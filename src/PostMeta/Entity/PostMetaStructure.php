<?php

namespace BackTo\Framework\PostMeta\Entity;

use BackTo\Framework\PostMeta\Contracts\PostMetaStructureInterface;

class PostMetaStructure implements PostMetaStructureInterface
{
    private string $objectType = 'post';
    private string $metaKey = '';
    private array $args = [];
    private string $objectSubtype = '';
    /** @see PostMetaType::class */
    private string $type = '';
    private string $label = '';
    private string $description = '';
    private bool $single = true;
    private mixed $default = null;
    /** @var callable|null $sanitizeCallback */
    private $sanitizeCallback = null;
    /** @var callable|null $authCallback */
    private $authCallback = null;
    private bool $showInRest = false;
    private bool $revisionsEnabled = false;

    public function getObjectType(): string
    {
        return $this->objectType;
    }

    public function setObjectType(string $objectType): PostMetaStructureInterface
    {
        $this->objectType = $objectType;
        return $this;
    }

    public function getMetaKey(): string
    {
        return $this->metaKey;
    }

    public function setMetaKey(string $metaKey): PostMetaStructureInterface
    {
        $this->metaKey = $metaKey;
        return $this;
    }

    public function getArgs(): array
    {
        return $this->args;
    }

    public function setArgs(array $args): PostMetaStructureInterface
    {
        $this->args = $args;
        return $this;
    }

    public function getObjectSubtype(): string
    {
        return $this->objectSubtype;
    }

    public function setObjectSubtype(string $objectSubtype): PostMetaStructureInterface
    {
        $this->objectSubtype = $objectSubtype;
        return $this;
    }

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): PostMetaStructureInterface
    {
        $this->type = $type;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): PostMetaStructureInterface
    {
        $this->label = $label;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): PostMetaStructureInterface
    {
        $this->description = $description;
        return $this;
    }

    public function isSingle(): bool
    {
        return $this->single;
    }

    public function setSingle(bool $single): PostMetaStructureInterface
    {
        $this->single = $single;
        return $this;
    }

    public function getDefault()
    {
        return $this->default;
    }

    public function setDefault($default): PostMetaStructureInterface
    {
        $this->default = $default;
        return $this;
    }

    public function getSanitizeCallback(): ?callable
    {
        return $this->sanitizeCallback;
    }

    public function setSanitizeCallback(callable $callback): PostMetaStructureInterface
    {
        $this->sanitizeCallback = $callback;
        return $this;
    }

    public function getAuthCallback(): ?callable
    {
        return $this->authCallback;
    }

    public function setAuthCallback(?callable $callback): PostMetaStructureInterface
    {
        $this->authCallback = $callback;
        return $this;
    }

    public function isShowInRest(): bool
    {
        return $this->showInRest;
    }

    public function dontShowInRest(): PostMetaStructureInterface
    {
        return $this->setShowInRest(false);
    }

    public function showInRest(): PostMetaStructureInterface
    {
        return $this->setShowInRest(true);
    }

    public function setShowInRest(bool $showInRest): PostMetaStructureInterface
    {
        $this->showInRest = $showInRest;
        return $this;
    }

    public function isRevisionsEnabled(): bool
    {
        return $this->revisionsEnabled;
    }

    public function setRevisionsEnabled(bool $enabled): PostMetaStructureInterface
    {
        $this->revisionsEnabled = $enabled;
        return $this;
    }
}