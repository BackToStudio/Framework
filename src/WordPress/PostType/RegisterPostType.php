<?php

namespace Fantassin\Core\WordPress\PostType;

use Exception;
use Fantassin\Core\WordPress\HasHooks;

class RegisterPostType implements HasHooks {

	/**
	 * @var PostTypeRepository
	 */
	private $repository;

	public function __construct( PostTypeRepository $postTypeRepository ) {
		$this->repository = $postTypeRepository;
	}

	public function hooks() {
		add_action( 'init', [ $this, 'registerPostType' ] );
		add_action( 'registered_post_type', [ $this, 'flushRules' ] );
	}

	public function flushRules() {
		flush_rewrite_rules();
	}

	public function registerPostType() {
		$repository = $this->getRepository();
		foreach ( $repository->getPostTypes() as $postType ) {

			if ( post_type_exists( $postType->getPostType() ) ) {
				return;
			}

			register_post_type( $postType->getPostType(), $postType->getArgs() );
		}
	}

	/**
	 * @return PostTypeRepository
	 */
	public function getRepository(): PostTypeRepository {
		return $this->repository;
	}


	/**
	 * Register new Custom Post Type on the fly.
	 *
	 * @param string $postType
	 * @param array $args
	 *
	 * @return $this
	 * @throws Exception
	 */
	public function add( string $postType, $args = [] ) {
		$repository  = $this->getRepository();
		$newPostType = new CustomPostType( $postType, '', '', $args );
		$repository->add( $newPostType );

		return $this;
	}
}
