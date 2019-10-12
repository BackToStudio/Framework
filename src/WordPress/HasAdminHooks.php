<?php

namespace Fantassin\Core\WordPress;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

interface HasAdminHooks {

  public function hooks();

}
