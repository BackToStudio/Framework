<?php

namespace Fantassin\Core\WordPress\PostType;

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
}
