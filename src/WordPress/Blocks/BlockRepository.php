<?php

namespace Fantassin\Core\WordPress\Blocks;

class BlockRepository {

	/**
	 * @var HasBlockName[]
	 */
	private $blocks = [];

	/**
	 * @param HasBlockName $block
	 *
	 * @return BlockRepository
	 */
	public function add( HasBlockName $block ): BlockRepository {
		$this->blocks[] = $block;

		return $this;
	}

	/**
	 * @return HasBlockName[]
	 */
	public function getBlocks(): array {
		return $this->blocks;
	}
}
