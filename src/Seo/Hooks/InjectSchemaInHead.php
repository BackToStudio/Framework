<?php

declare(strict_types=1);

namespace BackTo\Framework\Seo\Hooks;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Seo\Schema\SchemaManager;

/**
 * Inject JSON-LD structured data into the <head> via wp_head.
 */
class InjectSchemaInHead implements Hooks
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
        $this->hookDispatcher->addAction('wp_head', [$this, 'render'], 1);
    }

    public function render(): void
    {
        $output = $this->schemaManager->render();

        if ($output !== '') {
            echo $output . "\n";
        }
    }
}
