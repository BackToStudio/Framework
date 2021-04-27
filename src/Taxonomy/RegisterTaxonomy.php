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
        \add_action('registered_taxonomy', [$this, 'flushRules']);
    }

    public function flushRules()
    {
        \flush_rewrite_rules();
    }

    public function registerTaxonomy()
    {
        foreach ($this->getRepository()->getTaxonomies() as $taxonomy) {
            if (\taxonomy_exists($taxonomy->getTaxonomy())) {
                return;
            }

            \register_taxonomy($taxonomy->getTaxonomy(), $taxonomy->getPostTypes(), $taxonomy->getArgs());
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
