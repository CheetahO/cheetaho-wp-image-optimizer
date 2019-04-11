<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://cheetaho.com
 * @since      1.4.3
 * @package    CheetahO
 * @subpackage CheetahO/includes
 * @author     CheetahO <support@cheetaho.com>
 */

class Cheetaho_Deactivator {

	/**
	 * Do stuff on plugin deactivation
	 *
	 * @since    1.4.3
	 */
	public static function deactivate() {
		delete_option( 'cheetaho_activation_redirect' );
	}

}
