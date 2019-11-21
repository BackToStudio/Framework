<?php

namespace Fantassin\Core\WordPress;

use DI\Container as DIContainer;
use DI\ContainerBuilder;

class GenerateSaltKeys implements HasHooks {

	public function hooks() {
		add_action( 'init', [ $this, 'generateSaltKeys' ] );
	}

	public function generateSaltKeys() {
		$CP = 'pGhhgh@)@hNOKxIA6i%U2mfUZNf7G#ZV';
		$KP = 'z63F%VF62(Atw9Ti*N30Z8AFCGs9z(KY';
		$MK = __FILE__ . date( 'Ym' ) . $GLOBALS['blog_id'] . get_site_option( 'siteurl' );
		$CP .= $KP;
		$MK .= $KP;
		$U  = '_';
		$KS = array( 'KEY', 'SALT' );
		$KZ = array( 'AUTH', 'SECURE_AUTH', 'LOGGED_IN', 'NONCE', 'SECRET' );
		foreach ( $KS as $_KS ) {
			foreach ( $KZ as $_KZ ) {
				if ( ! defined( $_KZ . $U . $_KS ) ) {
					define( $_KZ . $U . $_KS, md5( 'BZK' . $_KZ . $_KS . md5( $MK ) . $MK ) . md5( $_KZ . $_KS . $MK ) );
				} else {
					wp_die( '<b>' . $_KZ . $U . $_KS . '</b> is already defined, please delete/comment all secret keys in your <i>wp-config.php</i> file.' );
				}
			}
		}
		define( 'COOKIEHASH', md5( 'BZKCOOKIEHASH' . md5( $MK . $CP ) . $MK . $CP ) . md5( 'BZKCOOKIEHASH' . $MK . $CP ) );
		unset( $U, $MK, $_KZ, $_KS, $KZ, $KS, $CP, $KP );
	}
}
