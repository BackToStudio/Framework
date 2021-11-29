<?php

namespace Fantassin\Core\WordPress\Taxonomy;

use Exception;
use Fantassin\Core\WordPress\Contracts\Hooks;

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

    public function __construct(TaxonomyRegistry $taxonomyRegistry, TaxonomyFactory $taxonomyFactory)
    {
        $this->registry = $taxonomyRegistry;
        $this->factory = $taxonomyFactory;
    }

    public function hooks()
    {
        \add_action('init', [$this, 'registerTaxonomy']);
        \add_action('registered_taxonomy', 'flush_rewrite_rules');
        \add_action('unregistered_taxonomy', 'flush_rewrite_rules');
    }

    public function registerTaxonomy()
    {
        foreach ($this->registry->getTaxonomies() as $taxonomy) {
            if (\taxonomy_exists($taxonomy->getKey())) {
                return;
            }

            try {
                $newTaxonomy = $this->factory->createTaxonomy($taxonomy->getKey(), $taxonomy->getPostTypes(), $taxonomy->getArgs());
                \register_taxonomy($newTaxonomy->getKey(), $newTaxonomy->getPostTypes(), $newTaxonomy->getArgs());
            } catch (Exception $exception) {
                write_log($exception->getMessage());
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
