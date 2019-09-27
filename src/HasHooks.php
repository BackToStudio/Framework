<?php

namespace Fantassin\Core;

if ( ! defined( 'ABSPATH' ) ) {
  exit; // Exit if accessed directly
}

interface HasHooks {

  public function hooks();

}
