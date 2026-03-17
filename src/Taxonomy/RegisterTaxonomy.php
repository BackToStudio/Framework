<?php

declare(strict_types=1);

namespace BackTo\Framework\Taxonomy;

use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Exception\FrameworkException;
use BackTo\Framework\Exception\InvalidTaxonomyException;
use BackTo\Framework\Taxonomy\Contracts\TaxonomyRegistrarInterface;

final class RegisterTaxonomy implements Hooks
{
    private readonly TaxonomyRegistry $registry;
    private readonly TaxonomyFactory $factory;
    private readonly TaxonomyRegistrarInterface $registrar;
    private readonly HookDispatcherInterface $hookDispatcher;

    public function __construct(
        TaxonomyRegistry $taxonomyRegistry,
        TaxonomyFactory $taxonomyFactory,
        TaxonomyRegistrarInterface $registrar,
        HookDispatcherInterface $hookDispatcher
    ) {
        $this->registry = $taxonomyRegistry;
        $this->factory = $taxonomyFactory;
        $this->registrar = $registrar;
        $this->hookDispatcher = $hookDispatcher;
    }

    public function hooks(): void
    {
        $this->hookDispatcher->addAction('init', [$this, 'registerTaxonomy']);
        $this->hookDispatcher->addAction('registered_taxonomy', [$this->registrar, 'flushRewriteRules']);
        $this->hookDispatcher->addAction('unregistered_taxonomy', [$this->registrar, 'flushRewriteRules']);
    }

    public function registerTaxonomy(): void
    {
        foreach ($this->registry->getTaxonomies() as $taxonomy) {
            if ($this->registrar->exists($taxonomy->getKey())) {
                return;
            }

            try {
                $newTaxonomy = $this->factory->createTaxonomy($taxonomy->getKey(), $taxonomy->getPostTypes(), $taxonomy->getArgs());
                $this->registrar->register($newTaxonomy->getKey(), $newTaxonomy->getPostTypes(), $newTaxonomy->getArgs());
            } catch (FrameworkException $exception) {
                \error_log($exception->getMessage());
            }
        }
    }

    /**
     * @param string[] $relatedPostTypes
     * @param array<string, mixed> $args
     *
     * @throws InvalidTaxonomyException
     */
    public function add(string $name, array $relatedPostTypes, array $args = []): self
    {
        $newTaxonomy = $this->factory->createTaxonomy($name, $relatedPostTypes, $args);
        $this->registry->add($newTaxonomy);

        return $this;
    }
}
