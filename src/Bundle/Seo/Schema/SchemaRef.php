<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Schema;

/**
 * Represents a JSON-LD @id reference to another node in the graph.
 *
 * Used to link schemas without duplicating data:
 *   "publisher": { "@id": "#organization" }
 */
final class SchemaRef implements \JsonSerializable
{
    private readonly string $id;

    public function __construct(string $id)
    {
        $this->id = $id;
    }

    public function getId(): string
    {
        return $this->id;
    }

    /**
     * @return array{@id: string}
     */
    public function toArray(): array
    {
        return ['@id' => $this->id];
    }

    public function jsonSerialize(): mixed
    {
        return $this->toArray();
    }
}
