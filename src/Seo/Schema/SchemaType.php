<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Schema;

/**
 * Base class for all schema.org types.
 *
 * Provides a fluent builder API for constructing JSON-LD structured data.
 */
class SchemaType implements \JsonSerializable
{
    protected string $type;

    /** @var array<string, mixed> */
    protected array $properties = [];

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    /**
     * Set an arbitrary property on the schema.
     *
     * @return $this
     */
    public function set(string $property, mixed $value): static
    {
        $this->properties[$property] = $value;

        return $this;
    }

    /**
     * Set the @id for this schema node.
     *
     * @return $this
     */
    public function id(string $id): static
    {
        return $this->set('@id', $id);
    }

    /**
     * Get the schema.org @type.
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Get all properties.
     *
     * @return array<string, mixed>
     */
    public function getProperties(): array
    {
        return $this->properties;
    }

    /**
     * Convert the schema to an associative array suitable for JSON-LD output.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $data = ['@type' => $this->type];

        foreach ($this->properties as $key => $value) {
            $data[$key] = $this->resolveValue($value);
        }

        return $data;
    }

    /**
     * Validate the schema against Google's required properties.
     *
     * Returns an array of missing required property names.
     * An empty array means the schema is valid for rich results.
     *
     * @return string[]
     */
    public function validate(): array
    {
        $required = $this->getRequiredProperties();

        if ($required === []) {
            return [];
        }

        $missing = [];

        foreach ($required as $property) {
            if (!isset($this->properties[$property])) {
                $missing[] = $property;
            }
        }

        return $missing;
    }

    /**
     * Whether this schema has all required properties for Google rich results.
     */
    public function isValid(): bool
    {
        return $this->validate() === [];
    }

    /**
     * Return the list of properties required by Google for this schema type.
     *
     * Override in concrete types to define required properties.
     *
     * @return string[]
     */
    protected function getRequiredProperties(): array
    {
        return [];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }

    /**
     * Resolve a value for JSON-LD output, handling nested SchemaType objects and arrays.
     */
    private function resolveValue(mixed $value): mixed
    {
        if ($value instanceof SchemaRef) {
            return $value->toArray();
        }

        if ($value instanceof self) {
            return $value->toArray();
        }

        if (\is_array($value)) {
            return array_map(fn (mixed $item): mixed => $this->resolveValue($item), $value);
        }

        return $value;
    }
}
