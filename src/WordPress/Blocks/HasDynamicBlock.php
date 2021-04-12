<?php

namespace Fantassin\Core\WordPress\Blocks;

/**
 * @deprecated
 */
interface HasDynamicBlock {

  public function renderBlock( array $attributes, string $content ): string;

}
