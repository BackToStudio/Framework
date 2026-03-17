<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Hooks;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Seo\Schema\SchemaManager;

/**
 * Add the SchemaManager to the Timber context for use in Twig templates.
 *
 * Usage in Twig: {{ schema.render()|raw }}
 */
class AddSchemaToTimberContext implements Hooks
{
    private SchemaManager $schemaManager;

    private HookDispatcherInterface $hookDispatcher;

    public function __construct(SchemaManager $schemaManager, HookDispatcherInterface $hookDispatcher)
    {
        $this->schemaManager = $schemaManager;
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addFilter('timber/context', [$this, 'addSchema']);
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function addSchema(array $context): array
    {
        $context['schema'] = $this->schemaManager;

        return $context;
    }
}
