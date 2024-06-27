<?php

namespace BackTo\Framework\Blocks;

/**
 * @deprecated
 */
class BlockRepository {

    public function __construct(){
        trigger_error('BackTo\Framework\Blocks\BlockRepository is deprecated, use BackTo\Framework\Blocks\BlockRegistry instead.', E_USER_DEPRECATED);
    }

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
