<?php

/**
 * Define the internationalization functionality
 *
 * Loads and defines the internationalization files for this plugin
 * so that it is ready for translation.
 *
 * @link       https://cheetaho.com
 * @since      1.4.3
 * @package    CheetahO
 * @subpackage CheetahO/includes
 * @author     CheetahO <support@cheetaho.com>
 */
class CheetahO_I18n {


	/**
	 * The ID of this plugin.
	 *
	 * @since    1.4.3
	 * @access   private
	 * @var      string $plugin_name The ID of this plugin.
	 */
	private $plugin_name;


	public function __construct( $plugin_name ) {
		$this->plugin_name = $plugin_name;
	}

	/**
	 * Load the plugin text domain for translation.
	 *
	 * @since    1.4.3
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain(
			$this->plugin_name,
			false,
			dirname( dirname( plugin_basename( __FILE__ ) ) ) . '/languages/'
		);
	}
}
