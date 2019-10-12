<?php

namespace Fantassin\Core\WordPress;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

interface HasHooks {

  public function hooks();

}
