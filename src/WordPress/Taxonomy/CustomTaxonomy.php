<?php

namespace Fantassin\Core\WordPress\Taxonomy;

use Exception;

class CustomTaxonomy {

	/**
	 * @var string
	 */
	private $taxonomy;

	/**
	 * @var array
	 */
	private $args;

	/**
	 * @var array
	 */
	private $postTypes;

	/**
	 * @var string
	 */
	private $singular;

	/**
	 * @var string
	 */
	private $plural;

	/**
	 * CustomPostType constructor.
	 *
	 * @param string $taxonomy
	 * @param string $singular
	 * @param string $plural
	 * @param array $postTypes
	 * @param array $args
	 *
	 * @throws Exception
	 */
	public function __construct( string $taxonomy, string $singular = '', string $plural = '', array $postTypes = [], array $args = [] ) {

		if ( empty( $taxonomy ) ) {
			throw new Exception( 'WordPress required taxonomy name. Name should only contain lowercase letters and the underscore character, and not be more than 32 characters long (database structure restriction).' );
		}

		$this->taxonomy = $taxonomy;

		if ( empty( $singular ) ) {
			$singular = $this->taxonomy;
		}

		$this->singular = $singular;

		if ( empty( $plural ) ) {
			$plural = $this->taxonomy;
		}

		$this->plural    = $plural;
		$this->postTypes = $postTypes;

		$this->args = array_merge( $args,
			[
				'hierarchical' => true, // Display checkbox in Post Type edit view
				'show_ui'      => true, // Display in WordPress Admin
				'show_in_rest' => true, // Allow Gutenberg editor
				'labels'       => $this->getLabels()
			]
		);
	}

	/**
	 * @return string
	 */
	public function getTaxonomy(): string {
		return $this->taxonomy;
	}

	/**
	 * @return array
	 */
	public function getArgs(): array {
		return $this->args;
	}


	/**
	 * Display minimum labels
	 *
	 * @return array
	 */
	private function getLabels() {
		return [
			'name'          => $this->getPlural(),
			'singular_name' => $this->getSingular(),
		];
	}

	/**
	 * @return string
	 */
	public function getPlural(): string {
		return $this->plural;
	}

	/**
	 * @return string
	 */
	public function getSingular(): string {
		return $this->singular;
	}

	/**
	 * @return array
	 */
	public function getPostTypes(): array {
		return $this->postTypes;
	}
}
