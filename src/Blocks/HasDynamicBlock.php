<?php

namespace BackTo\Framework\Blocks;

/**
 * @deprecated
 */
interface HasDynamicBlock {

  public function renderBlock( array $attributes, string $content ): string;

}
