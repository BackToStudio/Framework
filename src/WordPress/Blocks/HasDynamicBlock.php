<?php

namespace Fantassin\Core\WordPress\Blocks;

interface HasDynamicBlock {

  public function renderBlock( array $attributes, string $content ): string;

}
