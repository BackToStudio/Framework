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
            $this->registrar->register(
                $postMetaStructure->getObjectType(),
                (string) $postMetaStructure->getMetaKey(),
                $postMetaStructure->toRegistrationArgs()
            );
        }
    }
}
