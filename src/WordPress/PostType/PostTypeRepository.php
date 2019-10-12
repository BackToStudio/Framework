<?php

namespace Fantassin\Core\WordPress\PostType;

class PostTypeRepository {

	/**
	 * @var CustomPostType[]
	 */
	private $postTypes = [];

	/**
	 * @param CustomPostType $postType
	 *
	 * @return PostTypeRepository
	 */
	public function add( CustomPostType $postType ): PostTypeRepository {
		$this->postTypes[] = $postType;

		return $this;
	}

	/**
	 * @return array
	 */
	public function getPostTypes(): array {
		return $this->postTypes;
	}
}
