<?php

namespace Fantassin\Core\WordPress\Taxonomy;

use Fantassin\Core\WordPress\HasHooks;

class RegisterTaxonomy implements HasHooks {

	/**
	 * @var TaxonomyRepository
	 */
	private $repository;

	public function __construct( TaxonomyRepository $taxonomyRepository ) {
		$this->repository = $taxonomyRepository;
	}

	public function hooks() {
		add_action( 'init', [ $this, 'registerTaxonomy' ] );
		add_action( 'registered_taxonomy', [ $this, 'flushRules' ] );
	}

	public function flushRules() {
		flush_rewrite_rules();
	}

	public function registerTaxonomy() {
		$repository = $this->getRepository();
		foreach ( $repository->getTaxonomies() as $taxonomy ) {

			if ( taxonomy_exists( $taxonomy->getTaxonomy() ) ) {
				return;
			}

			register_taxonomy( $taxonomy->getTaxonomy(), $taxonomy->getPostTypes(), $taxonomy->getArgs() );
		}
	}

	/**
	 * @return TaxonomyRepository
	 */
	public function getRepository(): TaxonomyRepository {
		return $this->repository;
	}
}
