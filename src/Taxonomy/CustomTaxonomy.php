<?php

namespace Fantassin\Core\WordPress\Taxonomy;

use Exception;

/**
 * @deprecated
 */
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
		} else {
			trigger_error(
				sprintf(
					'Argument $singular is deprecated since %s, instead use RegisterTaxonomy->add($taxonomy, $postType, $args) to register a taxonomy.',
					'2.5.0'
				),
				E_USER_DEPRECATED
			);
		}

		$this->singular = $singular;

		if ( empty( $plural ) ) {
			$plural = $this->taxonomy;
		} else {
			trigger_error(
				sprintf(
					'Argument $plural is deprecated since %s, instead use RegisterTaxonomy->add($taxonomy, $postType, $args) to register a taxonomy.',
					'2.5.0'
				),
				E_USER_DEPRECATED
			);
		}

		$this->plural    = $plural;
		$this->postTypes = $postTypes;

		/**
		 * Prepare labels.
		 */
		$labels = $this->getLabels();

		if ( array_key_exists( 'labels', $args ) ) {
			$labels = array_merge( $labels, $args['labels'] );
		}

		$this->args = array_merge( $args,
			[
				'hierarchical' => true, // Display checkbox in Post Type edit view.
				'show_ui'      => true, // Display in WordPress Admin.
				'show_in_rest' => true, // Allow Gutenberg editor.
				'labels'       => $labels
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
	 * @deprecated Deprecated since version 2.5.0
	 * @return array
	 */
	private function getLabels() {
		return [
			'name'          => $this->getPlural(),
			'singular_name' => $this->getSingular(),
		];
	}

	/**
	 * @deprecated Deprecated since version 2.5.0
	 * @return string
	 */
	public function getPlural(): string {
		return $this->plural;
	}

	/**
	 * @deprecated Deprecated since version 2.5.0
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
