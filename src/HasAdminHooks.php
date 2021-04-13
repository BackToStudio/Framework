<?php

namespace Fantassin\Core\WordPress;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * @deprecated
 */
interface HasAdminHooks {

  public function hooks();

}
