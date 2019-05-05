<?php
/**
 * Fired during plugin activation
 *
 * @link       https://cheetaho.com
 * @since      1.4.3
 * @package    CheetahO
 * @subpackage CheetahO/includes
 * @author     CheetahO <support@cheetaho.com>
 */
class Cheetaho_Activator {
	/**
	 * Do stuff on plugin activation
	 *
	 * @since 1.4.3
	 */
	public static function activate() {
		add_option( 'cheetaho_activation_redirect', true );
		CheetahO_DB::check_custom_tables();
	}
}
