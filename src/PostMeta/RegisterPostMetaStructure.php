<?php

declare(strict_types=1);

namespace BackTo\Framework\PostMeta;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\PostMeta\Contracts\PostMetaRegistrarInterface;

final class RegisterPostMetaStructure implements Hooks
{

    private readonly PostMetaStructureRegistry $registry;

    private readonly PostMetaRegistrarInterface $registrar;

    private readonly HookDispatcherInterface $hookDispatcher;

    public function __construct(
        PostMetaStructureRegistry $postMetaStructureRegistry,
        PostMetaRegistrarInterface $registrar,
        HookDispatcherInterface $hookDispatcher
    ) {
        $this->registry = $postMetaStructureRegistry;
        $this->registrar = $registrar;
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('init', [$this, 'registerPostMeta']);
    }

    public function registerPostMeta(): void
    {
        foreach ($this->registry->getPostMetaStructures() as $postMetaStructure) {
            $args = [
                'object_subtype' => $postMetaStructure->getObjectSubtype(),
                'type' => $postMetaStructure->getType(),
                'label' => $postMetaStructure->getLabel(),
                'description' => $postMetaStructure->getDescription(),
                'single' => $postMetaStructure->isSingle(),
                'show_in_rest' => $postMetaStructure->isShowInRest(),
                'revisions_enabled' => $postMetaStructure->isRevisionsEnabled(),
            ];

            if (!is_null($postMetaStructure->getDefault())) {
                $args['default'] = $postMetaStructure->getDefault();
            }

            if (is_callable($postMetaStructure->getSanitizeCallback())) {
                $args['sanitize_callback'] = $postMetaStructure->getSanitizeCallback();
            }

            if (is_callable($postMetaStructure->getAuthCallback())) {
                $args['auth_callback'] = $postMetaStructure->getAuthCallback();
            }

            $this->registrar->register(
                $postMetaStructure->getObjectType(),
                $postMetaStructure->getMetaKey(),
                $args
            );
        }
    }
}
