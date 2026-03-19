<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta\Entity;

use BackTo\Framework\PostMeta\Contracts\MetaAccessControlInterface;
use BackTo\Framework\PostMeta\Contracts\MetaKeyAwareInterface;
use BackTo\Framework\PostMeta\Contracts\MetaSchemaInterface;
use BackTo\Framework\PostMeta\Contracts\PostMetaStructureInterface;
use BackTo\Framework\PostMeta\ValueObject\MetaKey;

final class PostMetaStructure implements PostMetaStructureInterface
{
    private string $objectType = 'post';
    private ?MetaKey $metaKey = null;
    /** @var array<string, mixed> */
    private array $args = [];
    private string $objectSubtype = '';
    private string $type = '';
    private string $label = '';
    private string $description = '';
    private bool $single = true;
    private mixed $default = null;
    /** @var callable|null */
    private mixed $sanitizeCallback = null;
    /** @var callable|null */
    private mixed $authCallback = null;
    private bool $showInRest = false;
    private bool $revisionsEnabled = false;

    // ── MetaKeyAwareInterface ───────────────────────────────

    public function getObjectType(): string
    {
        return $this->objectType;
    }

    public function setObjectType(string $objectType): MetaKeyAwareInterface
    {
        $this->objectType = $objectType;
        return $this;
    }

    public function getMetaKey(): MetaKey
    {
        if ($this->metaKey === null) {
            throw new \LogicException('MetaKey has not been set on this PostMetaStructure.');
        }
        return $this->metaKey;
    }

    public function setMetaKey(MetaKey|string $metaKey): MetaKeyAwareInterface
    {
        $this->metaKey = $metaKey instanceof MetaKey ? $metaKey : new MetaKey($metaKey);
        return $this;
    }

    public function getObjectSubtype(): string
    {
        return $this->objectSubtype;
    }

    public function setObjectSubtype(string $objectSubtype): MetaKeyAwareInterface
    {
        $this->objectSubtype = $objectSubtype;
        return $this;
    }

    // ── MetaSchemaInterface ─────────────────────────────────

    public function getType(): string
    {
        return $this->type;
    }

    public function setType(string $type): MetaSchemaInterface
    {
        $this->type = $type;
        return $this;
    }

    public function getLabel(): string
    {
        return $this->label;
    }

    public function setLabel(string $label): MetaSchemaInterface
    {
        $this->label = $label;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): MetaSchemaInterface
    {
        $this->description = $description;
        return $this;
    }

    public function isSingle(): bool
    {
        return $this->single;
    }

    public function setSingle(bool $single): MetaSchemaInterface
    {
        $this->single = $single;
        return $this;
    }

    public function getDefault(): mixed
    {
        return $this->default;
    }

    public function setDefault(mixed $default): MetaSchemaInterface
    {
        $this->default = $default;
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getArgs(): array
    {
        return $this->args;
    }

    /**
     * @param array<string, mixed> $args
     */
    public function setArgs(array $args): MetaSchemaInterface
    {
        $this->args = $args;
        return $this;
    }

    // ── MetaAccessControlInterface ──────────────────────────

    public function getSanitizeCallback(): ?callable
    {
        return $this->sanitizeCallback;
    }

    public function setSanitizeCallback(callable $callback): MetaAccessControlInterface
    {
        $this->sanitizeCallback = $callback;
        return $this;
    }

    public function getAuthCallback(): ?callable
    {
        return $this->authCallback;
    }

    public function setAuthCallback(?callable $callback): MetaAccessControlInterface
    {
        $this->authCallback = $callback;
        return $this;
    }

    public function isShowInRest(): bool
    {
        return $this->showInRest;
    }

    public function dontShowInRest(): MetaAccessControlInterface
    {
        return $this->setShowInRest(false);
    }

    public function showInRest(): MetaAccessControlInterface
    {
        return $this->setShowInRest(true);
    }

    public function setShowInRest(bool $showInRest): MetaAccessControlInterface
    {
        $this->showInRest = $showInRest;
        return $this;
    }

    public function isRevisionsEnabled(): bool
    {
        return $this->revisionsEnabled;
    }

    public function setRevisionsEnabled(bool $enabled): MetaAccessControlInterface
    {
        $this->revisionsEnabled = $enabled;
        return $this;
    }

    // ── Domain Logic ────────────────────────────────────────

    /**
     * Build the WordPress registration args array from this structure.
     *
     * Encapsulates the transformation from domain model to WordPress API,
     * eliminating Feature Envy in RegisterPostMetaStructure.
     *
     * @return array<string, mixed>
     */
    public function toRegistrationArgs(): array
    {
        $args = [
            'object_subtype' => $this->objectSubtype,
            'type' => $this->type,
            'label' => $this->label,
            'description' => $this->description,
            'single' => $this->single,
            'show_in_rest' => $this->showInRest,
            'revisions_enabled' => $this->revisionsEnabled,
        ];

        if ($this->default !== null) {
            $args['default'] = $this->default;
        }

        if (\is_callable($this->sanitizeCallback)) {
            $args['sanitize_callback'] = $this->sanitizeCallback;
        }

        if (\is_callable($this->authCallback)) {
            $args['auth_callback'] = $this->authCallback;
        }

        return $args;
    }
}
