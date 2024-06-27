<?php

namespace BackTo\Framework;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

/**
 * @deprecated
 */
interface HasAdminHooks {

  public function hooks();

}
