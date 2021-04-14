<?php

namespace Fantassin\Core\WordPress\PostType;

use Exception;

/**
 * @deprecated
 */
class CustomPostType {

	/**
	 * @var string
	 */
	private $postType;

	/**
	 * @var string
	 */
	private $singular;

	/**
	 * @var string
	 */
	private $plural;

	/**
	 * @var array
	 */
	private $args;

	/**
	 * CustomPostType constructor.
	 *
	 * @param string $postType
	 * @param string $singular
	 * @param string $plural
	 * @param array $args
	 *
	 * @throws Exception
	 */
	public function __construct( string $postType, string $singular = '', string $plural = '', array $args = [] ) {

        trigger_error( 'Fantassin\Core\WordPress\PostType\CustomPostType is deprecated, you can use Fantassin\Core\WordPress\PostType\PostTypeFactory to create new Post Type', E_USER_DEPRECATED);

        if ( empty( $postType ) ) {
			throw new Exception( 'WordPress required post type name. (max. 20 characters, cannot contain capital letters, underscores or spaces)' );
		}

		$this->postType = $postType;

		if ( empty( $singular ) ) {
			$singular = $this->postType;
		} else {
			trigger_error(
				sprintf(
					'Argument $singular is deprecated since %s, instead use RegisterPostType->add($postType, $args) to register a post type.',
					'2.5.0'
				),
				E_USER_DEPRECATED
			);
		}

		$this->singular = $singular;

		if ( empty( $plural ) ) {
			$plural = $this->postType;
		} else {
			trigger_error(
				sprintf(
					'Argument $plural is deprecated since %s, instead use RegisterPostType->add($postType, $args) to register a post type.',
					'2.5.0'
				),
				E_USER_DEPRECATED
			);
		}

		$this->plural = $plural;

		/**
		 * Prepare labels.
		 */
		$labels = $this->getLabels();

		if ( array_key_exists( 'labels', $args ) ) {
			$labels = array_merge( $labels, $args['labels'] );
		}

		$args = array_merge( $args,
			[
				'show_ui'            => true, // Display in WordPress Admin.
				'show_in_rest'       => true, // Allow Gutenberg editor.
				'publicly_queryable' => true, // Allow query.
				'labels'             => $labels,
			]
		);

		/**
		 * Add right supports when post type is hierarchical.
		 */
		if ( array_key_exists( 'hierarchical', $args ) && $args['hierarchical'] ) {
			$supports = [ 'page-attributes', 'editor', 'title' ];
			if ( array_key_exists( 'supports', $args ) ) {
				$supports = array_merge( $supports, $args['supports'] );
			}
			$args['supports'] = $supports;
		}

		$this->args = $args;
	}

	/**
	 * @return string
	 */
	public function getPostType(): string {
		return $this->postType;
	}

	/**
	 * @return array
	 */
	public function getArgs(): array {
		return $this->args;
	}

	/**
	 * @deprecated Deprecated since version 2.5.0
	 * @return string
	 */
	private function getPlural(): string {
		return $this->plural;
	}

	/**
	 * @deprecated Deprecated since version 2.5.0
	 * @return string
	 */
	private function getSingular(): string {
		return $this->singular;
	}


	/**
	 * Display minimum labels.
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
}
