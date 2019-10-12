<?php

namespace Fantassin\Core\WordPress\PostType;

use Exception;

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

		if ( empty( $postType ) ) {
			throw new Exception( 'WordPress required post type name. (max. 20 characters, cannot contain capital letters, underscores or spaces)' );
		}

		$this->postType = $postType;

		if ( empty( $singular ) ) {
			$singular = $this->postType;
		}

		$this->singular = $singular;

		if ( empty( $plural ) ) {
			$plural = $this->postType;
		}

		$this->plural = $plural;
		$this->args   = array_merge( $args,
			[
				'show_ui'      => true, // Display in WordPress Admin
				'show_in_rest' => true, // Allow Gutenberg editor
				'labels'       => $this->getLabels()
			]
		);
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
	 * @return string
	 */
	private function getPlural(): string {
		return $this->plural;
	}

	/**
	 * @return string
	 */
	private function getSingular(): string {
		return $this->singular;
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

}
