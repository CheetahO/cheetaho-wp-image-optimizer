<?php

/**
 * The admin-bulk optimization page functionality of the plugin.
 *
 * @link       https://cheetaho.com
 * @since      1.4.3
 * @package    CheetahO
 * @subpackage CheetahO/admin
 * @author     CheetahO <support@cheetaho.com>
 */
class CheetahO_Bulk {
    
	/**
	 * The version of this plugin.
	 *
	 * @since    1.4.3
	 * @access   private
	 * @var      string $version The current version of this plugin.
	 */

	private $version;

	/**
	 * For main cheetaho data object
	 *
	 * @var object $cheetaho
	 */
	private $cheetaho;

	/**
	 * Settings data.
	 *
	 * @since    1.4.3
	 * @access   private
	 * @var      array $cheetaho_settings
	 */
	private $cheetaho_settings;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since   1.4.3
	 * @param   string $plugin_name The name of this plugin.
	 * @param   string $version The version of this plugin.
	 * @param   array  $settings
	 */
	public function __construct( $cheetaho ) {
		$this->version           = $cheetaho->get_version();
		$this->cheetaho_settings = $cheetaho->get_cheetaho_settings();
		$this->cheetaho          = $cheetaho;
	}

	/**
	 * Register bulk optimization page
	 */
	function register_bulk_page() {
		add_media_page(
			'CheetahO Bulk Process',
			'Bulk CheetahO',
			'edit_others_posts',
			'cheetaho-bulk',
			array( $this, 'render_cheetaho_bulk_page' )
		);
	}


	/**
	 * Render settings page html
	 *
	 * @return mixed
	 */
	function render_cheetaho_bulk_page() {
		$images   = CheetahO_Stats::get_not_optimized_images($this->cheetaho_settings);

		include CHEETAHO_PLUGIN_ROOT . 'admin/views/bulk.php';
	}


}
