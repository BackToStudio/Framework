<?php

declare(strict_types=1);

namespace BackTo\Framework\Bundle\Seo\Schema\Generator;

use BackTo\Framework\Contracts\QueryContextInterface;
use BackTo\Framework\Bundle\Seo\Schema\SchemaType;

/**
 * Resolves a schema for a given post based on a post_type → generator mapping.
 *
 * The mapping is loaded from the `seo.php` configuration file:
 *
 *   return [
 *       'schema' => [
 *           'post_type_map' => [
 *               'post'       => \BackTo\Framework\Seo\Schema\Generator\ArticleSchemaGenerator::class,
 *               'formation'  => \App\Seo\CourseSchemaGenerator::class,
 *               'evenement'  => \App\Seo\EventSchemaGenerator::class,
 *           ],
 *       ],
 *   ];
 *
 * Each generator must implement a `generate(?int $postId): ?SchemaType` method.
 */
final class PostTypeSchemaResolver
{
    /** @var array<string, object> */
    private array $generators;

    /**
     * @param array<string, object> $generators Mapping of post_type => generator instance
     */
    public function __construct(array $generators = [])
    {
        $this->generators = $generators;
    }

    /**
     * Add a generator for a post type.
     *
     * @return $this
     */
    public function addGenerator(string $postType, object $generator): self
    {
        $this->generators[$postType] = $generator;

        return $this;
    }

    /**
     * Whether a generator is registered for the given post type.
     */
    public function supports(string $postType): bool
    {
        return isset($this->generators[$postType]);
    }

    /**
     * Resolve a schema for the given post.
     */
    public function resolve(string $postType, ?int $postId = null): ?SchemaType
    {
        if (!$this->supports($postType)) {
            return null;
        }

        $generator = $this->generators[$postType];

        return $generator->generate($postId);
    }

    /**
     * Resolve a schema for the current queried object, if applicable.
     *
     * Returns null when the current request is not a singular post
     * or when no generator is registered for the queried post type.
     */
    public function resolveFromContext(QueryContextInterface $queryContext): ?SchemaType
    {
        if (!$queryContext->isSingular()) {
            return null;
        }

        $post = $queryContext->getQueriedObject();

        if (!$post instanceof \WP_Post) {
            return null;
        }

        return $this->resolve($post->post_type, $post->ID);
    }

    /**
     * @return array<string, object>
     */
    public function getGenerators(): array
    {
        return $this->generators;
    }
}
