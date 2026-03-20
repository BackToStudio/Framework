<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Schema;

/**
 * Central registry for schema.org structured data.
 *
 * Collects SchemaType instances and renders them as a JSON-LD script block.
 */
final class SchemaManager
{
    /** @var SchemaType[] */
    private array $schemas = [];

    /**
     * Add a schema to the registry.
     *
     * @return $this
     */
    public function add(SchemaType $schema): self
    {
        $this->schemas[] = $schema;

        return $this;
    }

    /**
     * Whether any schemas have been registered.
     */
    public function hasSchemas(): bool
    {
        return $this->schemas !== [];
    }

    /**
     * Get all registered schemas.
     *
     * @return SchemaType[]
     */
    public function getSchemas(): array
    {
        return $this->schemas;
    }

    /**
     * Validate all registered schemas.
     *
     * Returns an array keyed by schema type with missing required properties.
     * An empty array means all schemas are valid.
     *
     * @return array<string, string[]>
     */
    public function validate(): array
    {
        $errors = [];

        foreach ($this->schemas as $schema) {
            $missing = $schema->validate();

            if ($missing !== []) {
                $errors[$schema->getType()] = $missing;
            }
        }

        return $errors;
    }

    /**
     * Export all schemas as an array of JSON-LD data.
     *
     * @return array<int, array<string, mixed>>
     */
    public function toArray(): array
    {
        return array_map(
            fn (SchemaType $schema): array => $schema->toArray(),
            $this->schemas,
        );
    }

    /**
     * Render all schemas as a JSON-LD <script> block for injection in <head>.
     *
     * Uses a @graph when multiple schemas are registered,
     * or a single object when only one is present.
     */
    public function render(): string
    {
        if (!$this->hasSchemas()) {
            return '';
        }

        $schemas = $this->toArray();

        if (\count($schemas) === 1) {
            $data = ['@context' => 'https://schema.org'] + $schemas[0];
        } else {
            $data = [
                '@context' => 'https://schema.org',
                '@graph' => $schemas,
            ];
        }

        $json = json_encode($data, \JSON_UNESCAPED_SLASHES | \JSON_UNESCAPED_UNICODE | \JSON_PRETTY_PRINT);

        return '<script type="application/ld+json">' . "\n" . $json . "\n" . '</script>';
    }
}
