<?php

namespace Fantassin\Core\WordPress\Taxonomy;

use Exception;
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

	/**
	 * Register new Taxonomy on the fly.
	 *
	 * @param string $taxonomy
	 * @param $postTypes
	 * @param array $args
	 *
	 * @return RegisterTaxonomy
	 * @throws Exception
	 */
	public function add( string $taxonomy, $postTypes, $args = [] ) {
		$repository  = $this->getRepository();
		$newPostType = new CustomTaxonomy( $taxonomy, '', '', $postTypes, $args );
		$repository->add( $newPostType );

		return $this;
	}
}
