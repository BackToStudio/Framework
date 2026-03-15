<?php

namespace BackTo\Framework\Taxonomy;

use Exception;
use BackTo\Framework\Contracts\HookDispatcherInterface;
use BackTo\Framework\Contracts\Hooks;
use BackTo\Framework\Taxonomy\Contracts\TaxonomyRegistrarInterface;

class RegisterTaxonomy implements Hooks
{

    /**
     * @var TaxonomyRegistry
     */
    private $registry;

    /**
     * @var TaxonomyFactory
     */
    private $factory;

    /**
     * @var TaxonomyRegistrarInterface
     */
    private $registrar;

    /**
     * @var HookDispatcherInterface
     */
    private $hookDispatcher;

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
            } catch (Exception $exception) {
                error_log($exception->getMessage());
            }
        }
    }

    /**
     * Register new Taxonomy on the fly.
     *
     * @param string $name
     * @param array $relatedPostTypes
     * @param array $args
     *
     * @return RegisterTaxonomy
     * @throws Exception
     */
    public function add(string $name, array $relatedPostTypes, array $args = []): RegisterTaxonomy
    {
        $newTaxonomy = $this->factory->createTaxonomy($name, $relatedPostTypes, $args);
        $this->registry->add($newTaxonomy);

        return $this;
    }
}
