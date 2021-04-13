<?php

namespace Fantassin\Core\WordPress;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * @deprecated
 */
interface HasHooks {

  public function hooks();

}
